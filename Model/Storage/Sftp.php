<?php
namespace Haerriz\GoogleShoppingFeed\Model\Storage;

use Haerriz\GoogleShoppingFeed\Api\Data\FeedProfileInterface;
use Haerriz\GoogleShoppingFeed\Api\CredentialProviderInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\Sftp as SftpIo;
use Magento\Framework\Exception\LocalizedException;

class Sftp implements AdapterInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var SftpIo
     */
    protected $sftpIo;

    /**
     * @var CredentialProviderInterface
     */
    protected $encryptor;

    /**
     * @param Filesystem $filesystem
     * @param SftpIo $sftpIo
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Filesystem $filesystem,
        SftpIo $sftpIo,
        CredentialProviderInterface $credentialProvider
    ) {
        $this->filesystem = $filesystem;
        $this->sftpIo = $sftpIo;
        $this->encryptor = $credentialProvider;
    }

    /**
     * @inheritdoc
     */
    public function upload(FeedProfileInterface $profile, $localFilePath)
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $absoluteLocalPath = $directory->getAbsolutePath($localFilePath);

        if (!$directory->isReadable($localFilePath)) {
            throw new LocalizedException(__('Local feed file is missing or not readable at: %1', $localFilePath));
        }

        try {
            $password = $profile->getDeliveryPassword();
            $decryptedPassword = $password ? $this->encryptor->decrypt($password) : '';

            $config = [
                'host' => $profile->getDeliveryHost() . ':' . ($profile->getDeliveryPort() ?: 22),
                'username' => $profile->getDeliveryUsername(),
                'password' => $decryptedPassword,
                'timeout' => max(1, (int)$profile->getData('delivery_timeout'))
            ];

            $this->sftpIo->open($config);

            $remoteDir = $profile->getDeliveryPath();
            if ($remoteDir) {
                $this->sftpIo->cd($remoteDir);
            }

            $filename = basename((string)($profile->getData('remote_filename') ?: $localFilePath));
            $temporary = '.' . $filename . '.' . bin2hex(random_bytes(8)) . '.tmp';
            $result = $this->sftpIo->write($temporary, $absoluteLocalPath);
            if ($result) {
                $result = $this->sftpIo->mv($temporary, $filename);
            }

            $this->sftpIo->close();

            if (!$result) {
                throw new LocalizedException(__('SFTP transfer failed for %1', $filename));
            }

            return true;
        } catch (\Exception $e) {
            throw new LocalizedException(__('SFTP upload failed. Verify the connection settings.'), $e);
        }
    }
}
