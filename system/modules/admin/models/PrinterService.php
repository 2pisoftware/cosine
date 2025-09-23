<?php

class PrinterService extends DbService
{

    /**
     * Gets printer object by ID
     *
     * @param mixed $printer_id
     * @return Printer|null
     */
    public function getPrinter($printer_id): Printer|null
    {
        return $this->getObject("Printer", $printer_id);
    }

    public function getPrinters(): array
    {
        return $this->getObjects("Printer");
    }

    public function getPrinterByName($printer_name)
    {
        return $this->getObject('Printer', ['name' => $printer_name]);
    }

    /**
     * Printjob sends a file to printer based on config rules
     *
     * @param string filename (Path to file to print)
     * @param Printer|null printer object to print to
     */
    public function printjob($filename, Printer|null $printer = null)
    {
        if (empty($filename)) {
            return;
        }

        // Log everywhere
        LogService::getInstance()->info("Starting print job: {$filename}");

        // Load print config
        $config = $this->w->moduleConf('admin', 'printing');
        if (!empty($config["command"])) {
            $command = '';
            // Get command based on OS
            switch (strtolower(substr(PHP_OS, 0, 3))) {
                case "win":
                    if (!empty($config["command"]["windows"])) {
                        $command = $config["command"]["windows"];
                    }
                    break;
                default:
                    if (!empty($config["command"]["unix"])) {
                        $command = $config["command"]["unix"];
                    }
                    break;
            }

            // Fill the string with our printer values
            if (!empty($printer->id)) {
                LogService::getInstance($this->w)->info("Printing to: {$printer->name} with command: {$command}");
                $command = str_replace(
                    search: ['$filename', '$servername', '$port', '$printername'],
                    replace: [$filename, escapeshellarg($printer->server), escapeshellarg($printer->port), escapeshellarg($printer->name)],
                    subject: $command
                );
            } else {
                $command = str_replace('$filename', escapeshellarg($filename), $command);
            }

            // Run the command
            $response = shell_exec($command." 2>&1");
            if (!empty($response)) {
                LogService::getInstance($this->w)->info("Shell exec response: {$response}");
            }

            return $response;
        }
    }
}
