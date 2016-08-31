{*
    Template to generate a PHP wrapper for the Apache CGI

    Available variables:
    $username           string
    $domain             string
    $reversedDomain     string
    $aliases            array|null
    $configDirectory    string
    $logDirectory       string
    $publicDirectory    string
    $phpVersion         string
    $phpBinary          string
    $phpWrapper         string
*}#!/bin/sh

export PHPRC={$configDirectory}
export PHP_FCGI_MAX_REQUESTS=500
export PHP_FCGI_CHILDREN=1

exec {$phpBinary} $@
