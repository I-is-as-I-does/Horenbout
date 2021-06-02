<?php
/* This file is part of Horenbout | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Horenbout;

use SSITU\Jack\Jack;

class Horenbout
{
    //@doc: source for valid colors: https://colorlib.com/etc/metro-colors.html

    protected $config = [];
    protected $remap;

    public function __construct($configOrPath = null)
    {
        $dflt = Jack::File()->readJson(dirname(__DIR__) . '/config/default-config.json');
        if (empty($dflt)) {
            return ['err' => 'default config not found'];
        }
        if (empty($configOrPath)) {
            $configOrPath = $dflt;
        } else {
            if (is_file($configOrPath)) {
                $configOrPath = Jack::File()->readJson($configOrPath);
            }
            if (!is_array($configOrPath) || empty($configOrPath)) {
                return ['err' => "invalid config or path: " . $configOrPath];
            }
            foreach ($dflt as $k => $v) {
                if (empty($configOrPath[$k])) {
                    $configOrPath[$k] = $v;
                }
            }
        }
        $this->config = $configOrPath;

    }

    private function reMapFiles()
    {
        $this->remap = [];
        foreach ($this->config['files'] as $group) {
            foreach ($group as $filedata) {
                $key = $filedata['width'];
                unset($filedata['width']);
                $this->remap[$key] = $filedata;
            }
        }
        return $this->remap;
    }

    private function saveFavicons($matches, $destDir)
    {
        $rslt = [];
        foreach ($matches as $filename => $tmpFile) {
            $dest = Jack::File()->reqTrailingSlash($destDir) . $filename;
            $save = move_uploaded_file($tmpFile, $dest);
            if ($save !== false) {
                $rslt['success'] = $dest;
            } else {
                $rslt['err'] = $dest;
            }
        }
        return $rslt;
    }

    public function validateNewFavicons($tmpFiles, $destDir = null)
    {
        if (empty($this->config)) {
            return ['err' => 'no valid config'];
        }
        if (empty($this->remap)) {
            $this->remap = $this->reMapFiles();
        }
        $rslt = ['matches' => [], 'fails' => []];
        $this->remap = $this->reMapFiles();
        foreach ($tmpFiles as $tmpFile) {
            $info = getimagesize($tmpFile);
            $width = $info[0];
            if (isset($this->remap[$width])) {
                $height = $info[1];
                $mime = $info['mime'];
                if ($this->remap[$width]['height'] == $height && in_array($mime, $this->remap[$width]['mime'])) {
                    $rslt['matches'][$this->remap[$width]['filename']] = $tmpFile;
                    unset($this->remap[$width]);
                }
            } else {
                $rslt['fails'][] = $tmpFile;
            }
        }
        if (!empty($matches) && $destDir !== null) {
            $rslt['save'] = $this->saveFavicons($rslt['matches'], $destDir);
        }
        return $rslt;
    }

    public function makeBrowserConfig($destDir, $aliasDir = null, $writeFile = true, $tileColor = null)
    {
        if (empty($this->config)) {
            return ['err' => 'no valid config'];
        }
        $refs = $this->getXmlRefs($destDir, $aliasDir);
        if (!empty($refs)) {
            $tileColor = $this->getTileColor($tileColor);
            $filecontent = $this->getXml($refs, $titleColor);
            if (empty($filecontent)) {
                return ['err' => 'xml template not found'];
            }
            if ($writeFile) {
                return Jack::File()->write($filecontent, Jack::File()->reqTrailingSlash($destDir) . $this->config['browserConfigName'] . '.xml', true);
            }
            return $filecontent;
        }
        return ['err' => 'favicons not found'];
    }

    private function getTileColor($tileColor)
    {
        if (empty($tileColor) || !in_array($tileColor, $this->config['validTileColors'])) {
            return $this->config['dfltTileColor'];
        }
        return $tileColor;
    }

    private function getXmlRefs($destDir, $aliasDir)
    {
        if (empty($aliasDir)) {
            $aliasDir = $destDir;
        }
        $refs = [];
        foreach ($this->config["files"]["mstile"] as $icondata) {
            $width = $icondata['width'];
            if (file_exists($destDir . $icondata['filename'])) {
                $refs[] = '<square' . $width . 'x' . $width . 'logo src="' . $aliasDir . $icondata['filename'] . '"/>';
            }
        }
        return $refs;
    }

    private function getXml($refs, $titleColor)
    {
        $path = __DIR__ . '/incl/browserconfig.php';
        $xmlHeader = '<?xml version="1.0" encoding="utf-8"?>';
        if (file_exists($path)) {
            ob_start();
            include $path;
            return ob_get_clean();
        }
        return false;
    }
}
