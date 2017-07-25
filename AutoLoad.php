class Zomposer
{
    /**
     * composer dir
     * @var
     */
    public $vendorDir;

    public function __construct($vendorDir = '')
    {
        if ($vendorDir) {
            $this->setVendorDir($vendorDir);
        }
    }

    /**
     * @return mixed
     */
    public function getVendorDir()
    {
        return $this->vendorDir;
    }

    /**
     * @param mixed $vendorDir
     */
    public function setVendorDir($vendorDir)
    {
        $this->vendorDir = $vendorDir;
    }

    public function getComposerDir()
    {
        return $this->vendorDir . DIRECTORY_SEPARATOR . 'composer';
    }

    public function getComposerId()
    {
        $fp = fopen($this->vendorDir . '/composer/autoload_real.php', 'r');
        $getComposerId = true;
        $i = 0;
        while ($getComposerId) {
            $lineStr = fgets($fp);
            if (strpos($lineStr, 'ComposerAutoloaderInit') !== false) {
                $getComposerId = false;
                fclose($fp);
                return explode('ComposerAutoloaderInit', $lineStr)[1];
            }
            if ($i++ > 7) {
                fclose($fp);
                return '';
            }
        }
        fclose($fp);
        return '';
    }

    public function getLoader()
    {
        $useStaticLoader =
            PHP_VERSION_ID >= 50600/*版本大于5.6*/
            && !defined('HHVM_VERSION')/*不是HHVM环境*/
            && (!function_exists('zend_loader_file_encoded') || !zend_loader_file_encoded());/*未定义zend_loader_file_encoded*/
        $autoLoader = new AutoLoad();
        if ($useStaticLoader) {
            require_once $this->getComposerDir() . '/autoload_static.php';
            $composerId = $this->getComposerId();
            $ComposerStaticInitName = 'ComposerStaticInit' . $composerId;
            $autoLoader->prefixLengthsPsr4 = $ComposerStaticInitName::$prefixLengthsPsr4;
            $autoLoader->prefixDirsPsr4 = $ComposerStaticInitName::$prefixDirsPsr4;
            $autoLoader->prefixesPsr0 = $ComposerStaticInitName::$prefixesPsr0;
            $autoLoader->classMap = $ComposerStaticInitName::$classMap;
            $includeFiles = $ComposerStaticInitName::$files;
        } else {
            $map = require $this->getComposerDir() . '/autoload_namespaces.php';
            foreach ($map as $namespace => $path) {
                $autoLoader->set($namespace, $path);
            }
            $map = require $this->getComposerDir() . '/autoload_psr4.php';
            foreach ($map as $namespace => $path) {
                $autoLoader->setPsr4($namespace, $path);
            }
            $classMap = require $this->getComposerDir() . '/autoload_classmap.php';
            if ($classMap) {
                $autoLoader->addClassMap($classMap);
            }
            $includeFiles = require $this->getComposerDir() . '/autoload_files.php';
        }
        $autoLoader->register(true);
        $this->includeFile($includeFiles);
        return $this;
    }

    public function includeFile($includeFiles = [])
    {
        foreach ($includeFiles as $fileIdentifier => $file) {
            if (empty($GLOBALS[$fileIdentifier])) {
                require $file;
                $GLOBALS[$fileIdentifier] = true;
            }
        }
    }
}
