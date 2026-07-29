<?php
$magentoRoot = getenv('MAGENTO_ROOT');
if (!$magentoRoot || !is_file($magentoRoot . '/app/bootstrap.php')) {
    throw new RuntimeException('Set MAGENTO_ROOT to a Magento installation before running module tests.');
}

require $magentoRoot . '/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create($magentoRoot, $_SERVER);
Magento\Framework\App\ObjectManager::setInstance($bootstrap->getObjectManager());

$moduleRoot = dirname(__DIR__);
spl_autoload_register(static function ($class) use ($moduleRoot) {
    $prefix = 'Haerriz\\GoogleShoppingFeed\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $file = $moduleRoot . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
}, true, true);
