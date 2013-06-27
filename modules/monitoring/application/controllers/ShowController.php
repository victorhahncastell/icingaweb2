<?php

use Icinga\Monitoring\Backend;
use Icinga\Web\ModuleActionController;
use Icinga\Web\Hook;
use Icinga\Application\Benchmark;

class Monitoring_ShowController extends ModuleActionController
{
    protected $backend;

    public function init()
    {
        $host = $this->_getParam('host');
        $service = $this->_getParam('service');
        if ($host !== null) {
            // TODO: $this->assertPermission('host/read', $host);
        }
        if ($service !== null) {
            // TODO: $this->assertPermission('service/read', $service);
        }
        // TODO: don't allow wildcards

        $this->backend = Backend::getInstance($this->_getParam('backend'));
        if ($service !== null && $service !== '*') {
            $this->view->service = $this->backend->fetchService($host, $service);
        }
        if ($host !== null) {
            $this->view->host = $this->backend->fetchHost($host);
        }
        $this->view->compact = $this->_getParam('view') === 'compact';
        $this->view->tabs = $this->createTabs();

        // If ticket hook:
        $params = array();
        if ($host !== null) {
            $params['host'] = $this->view->host->host_name;
        }
        if ($service !== null) {
            $params['service'] = $this->view->service->service_description;
        }
        if (Hook::has('ticket')) {
            $params['ticket'] = '__ID__';
            $this->view->ticket_link = preg_replace(
                '~__ID__~',
                '\$1',
                $this->view->qlink('#__ID__',
                    'monitoring/show/ticket',
                    $params
                )
            );
            // TODO: Global ticket pattern config (or per environment)
            $this->view->ticket_pattern = '~#(\d{4,6})~';
        }
    }

    public function serviceAction()
    {
        Benchmark::measure('Entered service action');
        $this->view->active = 'service';
        $this->view->tabs->activate('service')->enableSpecialActions();
        
        if ($grapher = Hook::get('grapher')) {
            if ($grapher->hasGraph(
                $this->view->host->host_name,
                $this->view->service->service_description
            )) {
                $this->view->preview_image = $grapher->getPreviewImage(
                    $this->view->host->host_name,
                    $this->view->service->service_description
                );
            }
        }

        $this->view->contacts = $this->backend->select()
            ->from('contact', array(
                'contact_name',
                'contact_alias',
                'contact_email',
                'contact_pager',
            ))
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->contactgroups = $this->backend->select()
            ->from('contactgroup', array(
                'contactgroup_name',
                'contactgroup_alias',
            ))
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->comments = $this->backend->select()
            ->from('comment', array(
                'comment_timestamp',
                'comment_author',
                'comment_data',
                'comment_type',
            ))
            ->where('service_host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->fetchAll();

        $this->view->customvars = $this->backend->select()
            ->from('customvar', array(
                'varname',
                'varvalue'
            ))
            ->where('varname', '-*PW*,-*PASS*')
            ->where('host_name', $this->view->host->host_name)
            ->where('service_description', $this->view->service->service_description)
            ->where('object_type', 'service')
            ->fetchPairs();
        Benchmark::measure('Service action done');
    }

    public function hostAction()
    {
        $this->view->active = 'host';
        $this->view->tabs->activate('host')->enableSpecialActions();
        
        if ($grapher = Hook::get('grapher')) {
            if ($grapher->hasGraph($this->view->host->host_name)) {
                $this->view->preview_image = $grapher->getPreviewImage(
                    $this->view->host->host_name
                );
            }
        }

        $this->view->hostgroups = $this->backend->select()
            ->from('hostgroup', array(
                'hostgroup_name',
                'hostgroup_alias'
            ))
            ->where('host_name', $this->view->host->host_name)
            ->fetchPairs();

        $this->view->contacts = $this->backend->select()
            ->from('contact', array(
                'contact_name',
                'contact_alias',
                'contact_email',
                'contact_pager',
            ))
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->contactgroups = $this->backend->select()
            ->from('contactgroup', array(
                'contactgroup_name',
                'contactgroup_alias',
            ))
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->comments = $this->backend->select()
            ->from('comment', array(
                'comment_timestamp',
                'comment_author',
                'comment_data',
                'comment_type',
            ))
            ->where('host_name', $this->view->host->host_name)
            ->fetchAll();

        $this->view->customvars = $this->backend->select()
            ->from('customvar', array(
                'varname',
                'varvalue'
            ))
            ->where('varname', '-*PW*,-*PASS*')
            ->where('host_name', $this->view->host->host_name)
            ->where('object_type', 'host')
            ->fetchPairs();
    }

    public function historyAction()
    {
        if ($this->view->host) {
            $this->view->tabs->activate('history')->enableSpecialActions();
        }
        $this->view->history = $this->backend->select()
            ->from('eventHistory', array(
                'object_type',
                'host_name',
                'service_description',
                'timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type'
            ))->applyRequest($this->_request);


            $this->view->preserve = $this->view->history->getAppliedFilter()->toParams();
    if ($this->_getParam('dump') === 'sql') {
        echo '<pre>' . htmlspecialchars($this->view->history->getQuery()->dump()) . '</pre>';
        exit;
    }
        if ($this->_getParam('sort')) {
            $this->view->preserve['sort'] = $this->_getParam('sort');
        }
    }

    public function servicesAction()
    {
        // Ugly and slow:
        $this->view->services = $this->view->action('services', 'list', 'monitoring', array(
            'host_name' => $this->view->host->host_name,
            //'sort', 'service_description'
        ));
    }

    public function ticketAction()
    {
        $this->view->tabs->activate('ticket')->enableSpecialActions();
        $id = $this->_getParam('ticket');
        // Still hardcoded, TODO: get URL:
        if (Hook::has('ticket')) {
            $ticketModule = 'rt';
            $this->render();
            $this->_forward('ticket', 'show', $ticketModule, array(
                'id' => $id
            ));
        }
    }

    protected function createTabs()
    {
        $tabs = $this->widget('tabs');
        if ( ! $this->view->host) {
            return $tabs;
        }
        $params = array(
            'host' => $this->view->host->host_name,
        );
        if ($backend = $this->_getParam('backend')) {
            $params['backend'] = $backend;
        }
        if (isset($this->view->service)) {
            $params['service'] = $this->view->service->service_description;
            $hostParams = $params + array('active' => 'host');
        } else {
            $hostParams = $params;
        }
        $tabs->add('host', array(
            'title'     => 'Host',
            'icon'      => 'img/classic/server.png',
            'url'       => 'monitoring/show/host',
            'urlParams' => $hostParams,
        ));
        $tabs->add('services', array(
            'title'     => 'Services',
            'icon'      => 'img/classic/service.png',
            'url'       => 'monitoring/show/services',
            'urlParams' => $params,
        ));
        if (isset($params['service'])) {
            $tabs->add('service', array(
                'title'     => 'Service',
                'icon'      => 'img/classic/service.png',
                'url'       => 'monitoring/show/service',
                'urlParams' => $params,
            ));
        }
        $tabs->add('history', array(
            'title'     => 'History',
            'icon'      => 'img/classic/history.gif',
            'url'       => 'monitoring/show/history',
            'urlParams' => $params,
        ));
        if ($this->action_name === 'ticket') {
            $tabs->add('ticket', array(
                'title'     => 'Ticket',
                'icon'      => 'img/classic/ticket.gif',
                'url'       => 'monitoring/show/ticket',
                'urlParams' => $params + array('ticket' => $this->_getParam('ticket')),
            ));
        }
/*
        $tabs->add('contacts', array(
            'title'     => 'Contacts',
            'icon'      => 'img/classic/customer.png',
            'url'       => 'monitoring/detail/contacts',
            'urlParams' => $params,
        ));
*/
        // TODO: Inventory 'img/classic/facts.gif'
        //       Ticket    'img/classic/ticket.gif'
        //       Customer  'img/classic/customer.png'
        return $tabs;
    }
}
