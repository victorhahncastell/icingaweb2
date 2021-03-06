<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Forms\Config\Authentication\DbBackendForm;
use Icinga\Forms\Config\Authentication\LdapBackendForm;
use Icinga\Forms\Config\Authentication\AutologinBackendForm;
use Icinga\Data\ConfigObject;

/**
 * Wizard page to define authentication backend specific details
 */
class AuthBackendPage extends Form
{
    /**
     * The resource configuration to use
     *
     * @var array
     */
    protected $config;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_authentication_backend');
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  self
     */
    public function setResourceConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Return the resource configuration as Config object
     *
     * @return  ConfigObject
     */
    public function getResourceConfig()
    {
        return new ConfigObject($this->config);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => $this->translate('Authentication Backend', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );

        if ($this->config['type'] === 'db') {
            $note = $this->translate(
                'As you\'ve chosen to use a database for authentication all you need '
                . 'to do now is defining a name for your first authentication backend.'
            );
        } elseif ($this->config['type'] === 'ldap') {
            $note = $this->translate(
                'Before you are able to authenticate using the LDAP connection defined earlier you need to'
                . ' provide some more information so that Icinga Web 2 is able to locate account details.'
            );
        } else { // if ($this->config['type'] === 'autologin'
            $note = $this->translate(
                'You\'ve chosen to authenticate using a web server\'s mechanism so it may be necessary'
                . ' to adjust usernames before any permissions, restrictions, etc. are being applied.'
            );
        }

        $this->addElement(
            'note',
            'description',
            array('value' => $note)
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        }

        if ($this->config['type'] === 'db') {
            $backendForm = new DbBackendForm();
            $backendForm->createElements($formData)->removeElement('resource');
        } elseif ($this->config['type'] === 'ldap') {
            $backendForm = new LdapBackendForm();
            $backendForm->createElements($formData)->removeElement('resource');
        } else { // $this->config['type'] === 'autologin'
            $backendForm = new AutologinBackendForm();
            $backendForm->createElements($formData);
        }

        $this->addElements($backendForm->getElements());
        $this->getElement('name')->setValue('icingaweb2');
    }

    /**
     * Validate the given form data and check whether it's possible to authenticate using the configured backend
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (false === isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            if ($this->config['type'] === 'ldap' && false === LdapBackendForm::isValidAuthenticationBackend($this)) {
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Add a checkbox to this form by which the user can skip the authentication validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'order'         => 2,
                'ignore'        => true,
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $this->translate('Check this to not to validate authentication using this backend')
            )
        );
    }
}
