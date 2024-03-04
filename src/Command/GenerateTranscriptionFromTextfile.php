<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:transgen', description: 'Generates a transcription')]
class GenerateTranscriptionFromTextfile extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Get a job from AristoteApi and treat it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new SymfonyStyle($input, $output);

        // Get the file path from where you want to read
        $filePath = 'public/trans.txt';

        // Open the file for reading
        $file = fopen($filePath, 'r');

        // Loop through each line of the file
        $sentences = [];
        $firstLine = true;
        $timeLine = true;
        $wholeText = null;
        while (!feof($file)) {
            $line = fgets($file);
            if ($timeLine) {
                if ($firstLine) {
                    $firstLine = false;
                } else {
                    $sentences[] = [
                        'start' => $startTime,
                        'end' => $this->convertTimestampToSeconds(rtrim($line)),
                        'text' => $text,
                    ];
                }
                $startTime = $this->convertTimestampToSeconds(rtrim($line));
            } else {
                $text = rtrim($line);
                $wholeText = $wholeText ? $wholeText.' '.$text : $text;
            }
            $timeLine = !$timeLine;
        }

        // Close the file
        fclose($file);
        $fileName = 'RI2';
        $transcript = [
            'original_file_name' => $fileName.'.mp4',
            'language' => 'fr',
            'sentences' => $sentences,
            'text' => $wholeText,
        ];
        // Convert the object to JSON format
        $jsonData = json_encode($transcript, JSON_PRETTY_PRINT);

        // Specify the file path where you want to save the JSON data
        $filePath = 'public/'.$fileName.'.json';
        file_put_contents($filePath, $jsonData);

        return Command::SUCCESS;
    }

    public function convertTimestampToSeconds(string $timestamp): int
    {
        // Explode the timestamp string into components
        $components = explode(':', $timestamp);

        // Reverse the array to start processing from the smallest unit
        $components = array_reverse($components);

        // Initialize the total seconds
        $totalSeconds = 0;

        // Define the multipliers for each unit (seconds, minutes, hours)
        $multipliers = [1, 60, 3600];

        // Loop through the components and calculate the total seconds
        foreach ($components as $index => $value) {
            $totalSeconds += (int) $value * $multipliers[$index];
        }

        return $totalSeconds;
    }
}
