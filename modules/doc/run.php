<?php

use \Zend_Controller_Router_Route;
use Icinga\Application\Icinga;

if (Icinga::app()->isCli()) {
    return;
}

$docModuleChapter = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/chapter/:chapterId',
    array(
        'controller'    => 'module',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$docIcingaWebChapter = new Zend_Controller_Router_Route(
    'doc/icingaweb/chapter/:chapterId',
    array(
        'controller'    => 'icingaweb',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$docModuleToc = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/toc',
    array(
        'controller'    => 'module',
        'action'        => 'toc',
        'module'        => 'doc'
    )
);

$docModulePdf = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/pdf',
    array(
        'controller'    => 'module',
        'action'        => 'pdf',
        'module'        => 'doc'
    )
);

$this->addRoute('doc/module/chapter', $docModuleChapter);
$this->addRoute('doc/icingaweb/chapter', $docIcingaWebChapter);
$this->addRoute('doc/module/toc', $docModuleToc);
$this->addRoute('doc/module/pdf', $docModulePdf);
