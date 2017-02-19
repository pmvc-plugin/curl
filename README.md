[![Latest Stable Version](https://poser.pugx.org/pmvc-plugin/curl/v/stable)](https://packagist.org/packages/pmvc-plugin/curl) 
[![Latest Unstable Version](https://poser.pugx.org/pmvc-plugin/curl/v/unstable)](https://packagist.org/packages/pmvc-plugin/curl) 
[![Build Status](https://travis-ci.org/pmvc-plugin/curl.svg?branch=master)](https://travis-ci.org/pmvc-plugin/curl)
[![License](https://poser.pugx.org/pmvc-plugin/curl/license)](https://packagist.org/packages/pmvc-plugin/curl)
[![Total Downloads](https://poser.pugx.org/pmvc-plugin/curl/downloads)](https://packagist.org/packages/pmvc-plugin/curl) 

# PMVC Curl Plugin 
===============

## if you want re-cook curl opiton, you need stringify url manually.
```
$curl = new CurlHelper();
$options = $curl->set();
$options[CURLOPT_URL] = (string)$options[CURLOPT_URL];
```

## Install with Composer
### 1. Download composer
   * mkdir test_folder
   * curl -sS https://getcomposer.org/installer | php

### 2. Install Use composer.json or use command-line directly
#### 2.1 Install Use composer.json
   * vim composer.json
```
{
    "require": {
        "pmvc-plugin/curl": "dev-master"
    }
}
```
   * php composer.phar install

#### 2.2 Or use composer command-line
   * php composer.phar require pmvc-plugin/curl
