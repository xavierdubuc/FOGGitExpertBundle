<?php
namespace FOG\GitExpertBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AfterPullCommand extends GitExpertCommand
{
    public static $INVALID = 0;
    public static $VALID = 1;
    public static $VALID_BUT_NOT_SYNC = 2;
    public static $VALIDITY_EXCEPTION = 3;
    
    protected function configure()
    {
        $this
            ->setName('git:after:pull')
            ->setDescription('Execute needed commands after a git pull')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset database')
            ->addOption('hard', null, InputOption::VALUE_NONE, 'rm -rf all cache subdirectory')
            ->setHelp(<<<EOT
The <info>%command.name%</info>command executes needed commands after a git pull.

  <info>php %command.full_name% name</info>

EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // RESET DATABASE
        if($input->hasOption('reset') && $input->getOption('reset'))
        {
            $this->dropDatabase($output);
            if(!$this->createDatabase($output)) return 2;
        }
        
        // DATABASE SCHEMA
        $schema_validity = $this->isSchemaValid($output);
        if($schema_validity === static::$VALID_BUT_NOT_SYNC)
            $this->updateSchema($output);
        else if($schema_validity === static::$INVALID)
            return 1;
        else if($schema_validity !== static::$VALIDITY_EXCEPTION && $schema_validity !== static::$VALID)
            $output->writeln($this->error('schema_validity : '.$schema_validity));

        // LOAD FIXTURE + FAKER DEMO CONTENT
        if($input->hasOption('reset') && $input->getOption('reset'))
        {
            $this->fixturesLoad($output);
            $this->fakerPopulate($output);
        }

        // UPDATE ELASTICA INDEXES
        $this->elasticaPopulate($output);
        
        // CLEAR CACHE (SOFLTY OR HARDLY)
        if($input->hasOption('hard') && $input->getOption('hard'))
        {
            $this->hardCacheClear($output, 'dev');
            $this->hardCacheClear($output, 'prod');
        }
        else
        {
            $this->softCacheClear($output, 'dev');
            $this->softCacheClear($output, 'prod');
        }
        // INSTALL ASSETS
        $this->assetsInstall($output);
    }

    private function dropDatabase($output)
    {
        $output->writeln($this->info('Dropping DB...'));
        $return_code = $this->executeCommand('doctrine:database:drop', $output, array('--force' => true), false);
        if($return_code === 0)
            $output->writeln($this->success('Database dropped.'));
        else if($return_code === 1)
            $output->writeln($this->error('Cannot drop database, maybe database does not exist or MySql server is not running or credentials in parameters.yml are incorrects ?'));
        else $output->writeln($this->error('dropDatabase:'.$return_code.'?'));
    }

    private function createDatabase($output)
    {
        $output->writeln($this->info('Creating DB...'));
        $return_code = $this->executeCommand('doctrine:database:create', $output, null, false);
        if($return_code === 0)
        {
            $output->writeln($this->success('Database created'));
            return true;
        }
        else if($return_code === 1)
            $output->writeln($this->error('Cannot create database, check if database is not already existing and if MySql server is running and also if the credentials in parameters.yml are corrects ?'));
        else $output->writeln($this->error('createDatabase:'.$return_code.'?'));
        return false;
    }
    
    private function isSchemaValid($output)
    {
        // DATABASE SCHEMA
        $this->getContainer()->get('database_connection')->close();
        $output->writeln($this->info('Checking if schema is valid...'));
        $return_code = $this->executeCommand('doctrine:schema:validate', $output, null, false);
        if($return_code === 0)
        {
            $output->writeln($this->success('Database schema is ok and sync !'));
            return static::$VALID;
        }
        else if($return_code === 1)
        {
            $output->writeln($this->error('Database schema is invalid ! Check it and then run command again.'));
            return static::$INVALID;
        }
        else if($return_code === 2)
        {
            $output->writeln($this->warning('Database schema is ok but not sync'));
            return static::$VALID_BUT_NOT_SYNC;
        }
        else if(is_object($return_code) && property_exists($return_code, 'message')) // It's an exception
        {
            $output->writeln($this->error('Cannot check if the database is synced with schema, do database exist ? Are the credentials in parameters.yml corrects ?'));
            return static::$VALIDITY_EXCEPTION;
        }
        else
        {
            return $return_code;
        }
    }

    private function updateSchema($output)
    {
        $output->writeln($this->info('Sync database with schema...'));
        $update_returnCode = $this->executeCommand('doctrine:schema:update', $output, array('--force' => true), false);
        if($update_returnCode === 0)
            $output->writeln($this->success('Database schema synced !'));
        else
            $output->writeln($this->error('Database schema syncing failed, run this command with --reset to reset the database.'));
    }

    private function fixturesLoad($output)
    {
        $output->writeln($this->info('Loading fixtures...'));
        $return_code = $this->executeCommand('doctrine:fixtures:load', $output, null, true, true);
        if($return_code === 0)
            $output->writeln($this->success('Fixtures loaded.'));
        else if($return_code) $output->writeln($this->error('fixtures:'.$return_code.'?'));
    }

    private function fakerPopulate($output)
    {
        $output->writeln($this->info('Loading demo content...'));
        $return_code = $this->executeCommand('faker:populate', $output, null, false);
        if($return_code === 0)
            $output->writeln($this->success('Demo content loaded'));
        else if($return_code) $output->writeln($this->error('faker:'.$return_code.'?'));
    }

    private function elasticaPopulate($output)
    {
        $output->writeln($this->info('Populating elastica indexes...'));
        $return_code = $this->executeCommand('fos:elastica:populate', $output, null, false);
        if($return_code === -42)
            $output->writeln($this->error('Cannot populate elastica\'s indexes if elastic search server is not running.'));
        else if(!is_null($return_code)) $output->writeln($this->success('Indexes populated.'));
    }

    private function softCacheClear($output,$cache)
    {
        $output->writeln($this->info('Clearing '.$cache.' cache...'));
        $return_code = $this->executeCommand('cache:clear', $output, array('--env' => $cache), false);
        if($return_code === 0)
            $output->writeln($this->success($cache.' cache cleared.'));
        else $output->writeln($this->error('cacheclear['.$cache.']:'.$return_code.'?'));
    }

    private function hardCacheClear($output,$cache)
    {
        $output->writeln($this->info('Hard remove '.$cache.' cache...'));
        shell_exec('rm -rf app/cache/'.$cache.'/*');
    }

    private function assetsInstall($output)
    {
        $output->writeln($this->info('Generating assets...'));
        $return_code = $this->executeCommand('assets:install', $output, null, false);
        if($return_code === 0)
            $output->writeln($this->success('Assets generated.'));
        else $output->writeln($this->error('assets:'.$return_code.' ?'));
    }
}