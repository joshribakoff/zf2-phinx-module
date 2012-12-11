<?php

namespace PhinxModule\Controller;

use Symfony\Component\Yaml\Yaml;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mvc\Controller\AbstractActionController;

class ConsoleController extends AbstractActionController
{
    /**
     * Sync database credentials with phinx.yml config
     *
     * @return String
     * @throws RuntimeException
     */
    public function syncAction()
    {
        /**
         * Enforce valid console request
         */
        $request = $this->getRequest();
        if (!$request instanceof ConsoleRequest){
            throw new \RuntimeException('You can only use this action from a console!');
        }


        /**
         * Load config
         */
        $config = $this->getServiceLocator()->get('config');

        if (!isset($config['db'])) {
            throw new \RuntimeException("Cannot find 'db' config section, unable to sync Phinx config!");
        }


        /**
         * Load Migrations dir
         */
        $migrations = $request->getParam('migrations', $config['phinx-module']['migrations']);


        /**
         * Extract details from DSN string
         */
        $dsn = "/^(\w+):dbname=(\w+);host=(\w+)$/i";
        if (!isset($config['db']['dsn'])
            || !preg_match($dsn, $config['db']['dsn'], $matches)) {
            throw new \RuntimeException("Unable to parse 'db' => 'dsn' connection string!");
        }


        /**
         * Build phinx config
         */
        $phinx = Array(
            'paths'        => Array('migrations' => $migrations),
            'environments' => Array(
                'default_migration_table' => 'phinxlog',
                'default_database'        => 'zf2',
                'zf2'                     => Array(
                    'adapter' => $matches[1],
                    'host'    => $matches[3],
                    'name'    => $matches[2],
                    'user'    => $config['db']['username'],
                    'pass'    => $config['db']['password'].",",
                    'port'    => 3306,
                ),
            ),
        );


        /**
         * Write YAML
         */
        $yaml = Yaml::dump($phinx);

        file_put_contents($config['phinx-module']['config'], $yaml);

        return "Phing config file written: {$config['phinx-module']['config']}\n";
    }
}
