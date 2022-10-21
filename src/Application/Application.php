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

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends BaseApplication
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $authorName;

    /**
     * @var string
     */
    private $authorMail;

    /**
     * @var string
     */
    private $licenseName;

    /**
     * @var string
     */
    private $licenseLink;

    /**
     * @param string $name
     * @param string $version
     * @param string $authorName
     * @param string $authorMail
     * @param string $licenseName
     * @param string $licenseLink
     */
    public function __construct(string $name, string $version, string $authorName, string $authorMail, string $licenseName, string $licenseLink)
    {
        parent::__construct($name, $version);

        $this->authorName = $authorName;
        $this->authorMail = $authorMail;
        $this->licenseName = $licenseName;
        $this->licenseLink = $licenseLink;
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
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getAuthorMail(): string
    {
        return $this->authorMail;
    }

    /**
     * @return string
     */
    public function getLicenseName(): string
    {
        return $this->licenseName;
    }

    /**
     * @return string
     */
    public function getLicenseLink(): string
    {
        return $this->licenseLink;
    }

    /**
     * @return null|string
     */
    public function getGitHash(): ?string
    {
        $hash = '@git-commit@';

        if ('@'.'git-commit@' !== $hash) {
            return substr($hash, 0, 7);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getLongVersion(): string
    {
        $v = sprintf('%s by <comment>%s \<%s></comment>', parent::getLongVersion(), $this->getAuthorName(), $this->getAuthorMail());

        if (null !== ($hash = $this->getGitHash())) {
            $v .= sprintf(' (%s)', $hash);
        }

        return sprintf('%s [%s \<%s>]', $v, $this->getLicenseName(), $this->getLicenseLink());
    }
}
