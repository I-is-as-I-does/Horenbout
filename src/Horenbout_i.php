<?php
/* This file is part of Horenbout | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Horenbout;

interface Horenbout_i
{
    public function __construct($config);
    public function makeBrowserConfig($zone, $writeFile = true);
    public function validateNewFavicons($zone, $tmpFiles, $saveIfValid = true);
}
