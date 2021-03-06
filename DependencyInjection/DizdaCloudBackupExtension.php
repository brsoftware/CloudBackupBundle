<?php

namespace Dizda\CloudBackupBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DizdaCloudBackupExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        /* Config output file */
        $container->setParameter('dizda_cloud_backup.root_folder', $container->getParameter('kernel.root_dir') . '/../');
        $container->setParameter('dizda_cloud_backup.output_folder', $container->getParameter('kernel.cache_dir') . '/backup/');

        foreach ($config as $k => $v) {
            $container->setParameter('dizda_cloud_backup.' . $k, $v);
        }

        /* Config google drive */
        if (isset($config['cloud_storages']['google_drive'])) {
            if (!class_exists('Happyr\GoogleSiteAuthenticatorBundle\Service\ClientProvider')) {
                throw new \LogicException('DizdaCloudBundle: You need to install and configure Happyr/GoogleSiteAuthenticatorBundle to be able to use Google Drive as remote storage.');
            }

            $dev = $container->getDefinition('dizda.cloudbackup.client.google_drive');
            $dev->setPublic(true)
                ->replaceArgument(0, new Reference('happyr.google_site_authenticator.client_provider'))
                ->replaceArgument(1, $config['cloud_storages']['google_drive']['token_name'])
                ->replaceArgument(2, $config['cloud_storages']['google_drive']['remote_path']);
        }
//        else {
//            $this->setDefaultsParameters($container, array( 'dizda_cloud_backup.cloud_storages.google_drive.active'));
//        }
        if (isset($config['cloud_storages']['dropbox'])) {
            if (!class_exists('DropboxUploader')) {
                throw new \LogicException('You need to add "hakre/dropbox-uploader" to your composer.json');
            }
        }

        // When we launch functional tests, there is no DB specified, so skip it if empty
        if (!$container->hasParameter('dizda_cloud_backup.databases')) {
            $container->setParameter('dizda_cloud_backup.databases', array());
        }

        if (!$container->hasParameter('dizda_cloud_backup.cloud_storages')) {
            $container->setParameter('dizda_cloud_backup.cloud_storages', array());
        }

        $databases = $container->getParameter('dizda_cloud_backup.databases');

        // Setting mysql values
        if (isset($config['databases']['mysql'])) {
            $mysql     = $databases['mysql'];

            if ($mysql['database'] === null) {
                $mysql['database'] = $container->getParameter('database_name');
            }

            /* if mysql config is not set, we taking from the parameters.yml values */
            if ($mysql['db_host'] === null && $mysql['db_user'] === null) {

                $mysql['db_host'] = $container->getParameter('database_host');

                if($container->getParameter('database_port') !== null) {

                    $mysql['db_port'] = $container->getParameter('database_port');
                }

                $mysql['db_user']     = $container->getParameter('database_user');
                $mysql['db_password'] = $container->getParameter('database_password');
            }

            $databases['mysql'] = $mysql;
        }

        // Setting postgresql values
        if (isset($config['databases']['postgresql'])) {
            $postgresql = $databases['postgresql'];

            if ($postgresql['database'] === null) {
                $postgresql['database'] = $container->getParameter('database_name');
            }

            /* if postgresql config is not set, we taking from the parameters.yml values */
            if ($postgresql['db_host'] === null && $postgresql['db_user'] === null) {

                $postgresql['db_host'] = $container->getParameter('database_host');

                if($container->getParameter('database_port') !== null) {

                    $postgresql['db_port'] = $container->getParameter('database_port');
                }

                $postgresql['db_user']     = $container->getParameter('database_user');
                $postgresql['db_password'] = $container->getParameter('database_password');
            }

            $databases['postgresql'] = $postgresql;
        }

        $container->setParameter('dizda_cloud_backup.databases', $databases);

    }
}
