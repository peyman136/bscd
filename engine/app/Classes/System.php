<?php

namespace App\Classes;

use \Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class System
{
    protected $salt = '46e621905e032ff9a8effc5905a87c791b095e16b1773f7f6a631ab39c8e4885'; // change in every release
    protected $words = [
        "algorithm", "database", "compiler", "encryption", "protocol",
        "debugging", "variable", "function", "framework", "iteration",
        "recursion", "inheritance", "polymorphism", "interface", "abstraction",
        "virtualization", "containerization", "microservices", "encryption",
        "authentication", "authorization", "responsive", "asynchronous", "cache", "refactoring",
        "lion", "tiger", "elephant", "giraffe", "kangaroo",
        "zebra", "panda", "dolphin",
        "paris", "london", "tokyo", "newyork", "berlin",
        "moscow", "dubai", "sydney",
        "apple", "banana", "cherry", "mango", "pineapple",
        "grape", "strawberry", "kiwi", "pomegranate"
    ]; // change in every release
    private function shuffle(string $word): string {
        $seed = hexdec(substr(hash('sha256', $word), 0, 8));
        $chars = str_split($word);

        mt_srand($seed);

        usort($chars, function ($a, $b) use ($seed) {
            return hexdec(substr(hash('sha256', $seed . $a), 0, 4)) <=>
                hexdec(substr(hash('sha256', $seed . $b), 0, 4));
        });

        mt_srand();
        return implode('', $chars);
    }

    private function generateSalt($rev = false)
    {
        $words = $this->words;
        if($rev === false) {
            sort($words);
        }else {
            rsort($words);
        }
        $processedWords = array_map(fn($word) => substr($this->shuffle($word), 0, intdiv(strlen($word), 2)), $words);

        return hash_hmac('sha256', implode('|', $processedWords), $this->salt);
    }

    public function getKey()
    {
        try {
            $cacheSalt = substr($this->generateSalt(true), 1,32);

            $newEncrypter = new \Illuminate\Encryption\Encrypter($cacheSalt, config('app.cipher'));
            return $newEncrypter->decrypt(Cache::remember('bscdkey',11000,function () use ($newEncrypter){
                $cpuId = $this->getCpuId();
                $uuid = $this->getSystemUUID();
                $mac = $this->getMacAddress();
                $diskSerial = $this->getDiskSerial();

                $salt = $this->generateSalt();
                $rawData = "$cpuId|$uuid|$mac|$diskSerial|$salt";
                $key = hash('sha256', $rawData);
                return $newEncrypter->encrypt($key);
            }));

        } catch (ProcessFailedException $e) {
            //$this->error('Error executing process: ' . $e->getMessage());
        }
    }
    private function runProcess(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->cleanOutput($process->getOutput());
    }

    /*private function getCpuId(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->runProcess('wmic cpu get ProcessorId 2>nul | findstr /r /v "^$"');
        } else {
            $cpuId = $this->runProcess("cat /proc/cpuinfo | grep 'Serial' | awk '{print $3}'");
            if (empty($cpuId)) {
                $cpuId = $this->runProcess("sudo dmidecode -t processor | grep ID | awk '{print $3$4$5$6}'");
            }
            return !empty($cpuId) ? $cpuId : 'UNKNOWN';
        }
    }*/

    private function getCpuId(): string
    {
        return $this->runProcess(PHP_OS_FAMILY === 'Windows' ?
            'wmic cpu get ProcessorId 2>nul | findstr /r /v "^$"' :
            "lscpu | grep 'Model name' | awk '{print $3$4$5}'");
    }

    private function getSystemUUID(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->runProcess('wmic csproduct get UUID 2>nul | findstr /r /v "^$"');
        } else {
            return $this->runProcess('cat /sys/class/dmi/id/product_uuid');
        }
    }

    private function getMacAddress(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->runProcess('powershell -Command "(Get-NetAdapter | Where-Object { $_.Status -eq \'Up\' }).MacAddress | Select -First 1"');
        } else {
            return $this->runProcess("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address");
        }
    }

    /*private function getDiskSerial(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->runProcess('wmic diskdrive get SerialNumber 2>nul | findstr /r /v "^$"');
        } else {
            return $this->runProcess('lsblk -no SERIAL /dev/sda');
        }
    }*/

    private function getDiskSerial(): string
    {
        return $this->runProcess(PHP_OS_FAMILY === 'Windows' ?
            'wmic diskdrive get SerialNumber 2>nul | findstr /r /v "^$"' :
            'udevadm info --query=property --name=sda | grep ID_SERIAL_SHORT | cut -d "=" -f2');
    }
    private function cleanOutput(string $output): string
    {
        // حذف فضای خالی، خطوط جدید و تمیز کردن خروجی
        $output = str_replace(['ProcessorId','ID:','Serial Number:','UUID','Model Number:','serialnumber','SerialNumber'], '',$output);
        return strtoupper(preg_replace('/\s+/', '', trim($output)));
    }
}
