<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

require_once dirname(__FILE__) . '/ApplicationBootstrap.php';

/**
 * Use this if you want to make use of Icinga functionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 */
class EmbeddedWeb extends ApplicationBootstrap
{
    /**
     * Embedded bootstrap parts
     *
     * @see    ApplicationBootstrap::bootstrap
     * @return self
     */
    protected function bootstrap()
    {
        return $this
            ->setupZendAutoloader()
            ->loadConfig()
            ->setupErrorHandling()
            ->setupTimezone()
            ->setupModuleManager()
            ->loadEnabledModules();
    }
}
