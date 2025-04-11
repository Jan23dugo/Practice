<?php

require 'vendor/autoload.php';

use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;

class DocumentAITest {
    private $client;
    private $projectId;
    private $location;

    public function __construct($credentialsPath) {
        $this->projectId = 'document-ai-demo-456309';
        $this->location = 'us';
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        $this->client = new DocumentProcessorServiceClient();
    }

    public function testConnection() {
        try {
            $parent = $this->client->locationName($this->projectId, $this->location);
            $processors = $this->client->listProcessors($parent);

            echo "Available Processors:<br>";
            foreach ($processors as $processor) {
                echo "Processor: " . $processor->getName() . "<br>";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}

$test = new DocumentAITest('config/document-ai-demo-456309-16d7de1fbb5e.json');
$test->testConnection();

?> 