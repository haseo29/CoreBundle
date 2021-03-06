<?php

namespace Blast\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PatcherApplyCommand extends ContainerAwareCommand
{

    // EXAMPLE COMMANDS

    /**
     * Command applies patch file [targetFilePath, patchPath]
     *
     * @var string
     */
    private $command = 'patch -f %1$s < %2$s';

    use PatcherConfig,
        PatcherLogger;

    protected function configure()
    {
        $this
            ->setName('librinfo:patchs:apply')
            ->setDescription('Apply Patches from Librinfo on misc vendors');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig();

        foreach ($this->config['patches'] as $patch)
            if ($patch['enabled'] === true && $patch['patched'] == false)
                $this->applyPatch($patch['targetFile'], $patch['patchFile'], $patch['id']);

        $this->displayMessages($output);
    }

    private function applyPatch($targetFile, $patchFile, $patchId)
    {
        $targetFile = $this->config['paths']['rootDir'] . '/' . $targetFile;

        if (!file_exists($targetFile) || !file_exists($patchFile))
        {
            $this->error('Missing patches :');
            if (!file_exists($targetFile))
                $this->comment(' - ' . $targetFile);
            if (!file_exists($patchFile))
                $this->comment(' - ' . $patchFile);
            return;
        }

        $command = sprintf(
            $this->command,
            $targetFile,
            $patchFile
        );
        $out = null;
        system($command, $out);

        if ($out != 0)
            $this->error("The patch " . $patchFile . " has not been applyed on file " . $targetFile);
        else
        {
            foreach ($this->config['patches'] as $key => $patch)
                if ($patch['id'] == $patchId)
                    $this->config['patches'][$key]['patched'] = true;

            file_put_contents(
                $this->config['paths']['configFile'],
                Yaml::dump(
                    ['patches' => $this->config['patches']]
                )
            );
        }

    }
}