<?php
namespace FOG\GitExpertBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use \InvalidArgumentException;

abstract class GitExpertCommand extends ContainerAwareCommand
{
    public static $INVALID = 0;
    public static $VALID = 1;
    public static $VALID_BUT_NOT_SYNC = 2;
    public static $VALIDITY_EXCEPTION = 3; 

    protected function executeCommand($command_name, $output, $args = null, $verbose = true, $no_interaction = false)
    {
        try
        {
            $input_array = array('command' => $command_name);
            $input = new ArrayInput(is_null($args) ? $input_array : array_merge($input_array, $args));
            if($no_interaction)
                $input->setInteractive (false);
            $command = $this->getApplication()->find($command_name);
            if(!$verbose)
                $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            $returnCode = $command->run($input,$output);
            $out = $returnCode;
        }
        catch(InvalidArgumentException $e)
        {
            $output->writeln($this->warning('Command "'.$command_name.'" does not exist, maybe you need to run composer install in order to install and enable the bundle defining this command ?'));
            $output->writeln($this->warning('(You can ignore this message if you don\'t need this command to be executed and/or don\'t need this bundle functionnality in this project)'));
            $out = null;
        }
        catch(\Exception $e)
        {
            $out = $e;
        }
        if(!$verbose)
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        
        return $out;
    }

    protected function format($message, $foreground = null, $background = null)
    {
        if(is_null($foreground) && is_null($background))
            return $message;
        else if(!is_null($foreground) && is_null($background))
        {
            if($foreground === 'success')
                return $this->format($message,'white','green');
            else if($foreground === 'warning')
                return $this->format($message,'black','yellow');
            return '<'.$foreground.'>' . $message . '</'.$foreground.'>';
        }
        else if(is_null($foreground) && !is_null($background))
        {
            return '<bg='.$background.'>' . $message . '</bg='.$background.'>';
        }
        else if(!is_null($foreground) && !is_null($background))
        {
            return '<fg='.$foreground.';bg='.$background.'>' . $message . '</fg='.$foreground.';bg='.$background.'>';
        }
        else return $message;
    }

    protected function success($message)
    {
        return $this->format($message,'success');
    }

    protected function info($message)
    {
        return $this->format($message,'info');
    }

    protected function error($message)
    {
        return $this->format($message,'error');
    }

    protected function warning($message)
    {
        return $this->format($message,'warning');
    }
}