<?php

declare(strict_types=1);

namespace Heartide\AliYun\Sls\Request;

/**
 * Class Request
 * @package Heartide\AliYun\Sls\Request
 */
class Request
{
    /**
     * @var string project name
     */
    private $project;

    /**
     * Request constructor
     * @param string $project
     *            project name
     */
    public function __construct($project)
    {
        $this->project = $project;
    }

    /**
     * Get project name
     * @return string project name
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set project name
     * @param string $project
     *            project name
     */
    public function setProject($project)
    {
        $this->project = $project;
    }
}