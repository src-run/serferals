<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SerferalsApplication
 */
class SerferalsApplication extends Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $author;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $license;

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct($name, $version, $author, $email, $license)
    {
        parent::__construct($name, $version);

        $this->author = $author;
        $this->email = $email;
        $this->license = $license;
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Overridden so that the application does not expect the command
     * name to be the first argument.
     *
     * @return InputDefinition
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @return null|string
     */
    public function getGitHash()
    {
        $gitCommit = '@git-commit@';

        if ('@'.'git-commit@' !== $gitCommit) {
            return $gitCommit;
        }

        return null;
    }

    /**
     * Overridden so the command name doesn't need to be specified on
     * the cli, it is instead provided here nativly.
     *
     * @param InputInterface $input
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        return 'scan';
    }

    /**
     * @return string
     */
    public function getLongVersion()
    {
        $version = sprintf(
            '%s by <comment>%s \<%s></comment>',
            parent::getLongVersion(),
            $this->author,
            $this->email
        );

        if (null !== ($gitCommit = $this->getGitHash())) {
            $version .= sprintf(' (%s)', $gitCommit);
        }

        return $version;
    }
}

/* EOF */
