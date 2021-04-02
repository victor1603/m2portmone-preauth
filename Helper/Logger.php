<?php

namespace CodeCustom\PortmonePreAuthorization\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Laminas\Log\Writer\Stream;
use Laminas\Log\Logger as LaminasLogger;

class Logger
{

    const TYPE = 'code_custom';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var null
     */
    protected $customLogger = null;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var string
     */
    private $logFolder = '/log/';

    /**
     * @var string
     */
    private $logDateFolder = '/';

    /**
     * @var array
     */
    public $logPath = [];

    /**
     * Logger constructor.
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @param string $log_name
     * @param string $fileFolder
     * @return \CodeCustom\PortmonePreAuthorization\Helper\Logger
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function create($log_name = 'log_file', $fileFolder = '')
    {
        $fileFolder = $fileFolder ? '/' . $fileFolder: '';
        $logDateFolder = '/' . date('Y') . '/' . date('m') . '/' . date('d');
        $logFolder = '/log/' . self::TYPE . $fileFolder . $logDateFolder;
        $logfile = '/' . $log_name . '_' . date('H_i_s');
        $this->file->mkdir($this->directoryList->getPath('var') . $logFolder, 0775);
        $writer = new Stream(BP . '/var' . $logFolder . $logfile . '.log');
        $logger = new LaminasLogger();
        $logger->addWriter($writer);
        $this->customLogger = $logger;
        $this->logPath[] = '/var' . $logFolder . $logfile . '.log';
        return $logger;
    }

    /**
     * @param $messageData
     * @return array|false
     */
    public function log($messageData = null)
    {
        if (!$this->customLogger || !$messageData) {
            return false;
        }

        if (is_array($messageData) || is_object($messageData)) {
            foreach ($messageData as $key => $value) {
                $this->customLogger->info($key . ': ' . $value);
            }
        } elseif ($messageData) {
            $this->customLogger->info('Log: ' . $messageData);
        }

        return $this->logPath;
    }
}
