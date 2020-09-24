# Laravel Install Wizard

<a href="https://packagist.org/packages/rubensrocha/lara-install-wizard"><img src="https://poser.pugx.org/rubensrocha/lara-install-wizard/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/rubensrocha/lara-install-wizard"><img src="https://poser.pugx.org/rubensrocha/lara-install-wizard/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/rubensrocha/lara-install-wizard"><img src="https://poser.pugx.org/rubensrocha/lara-install-wizard/license.svg" alt="License"></a>

## Description

This package was created to implement new features in the official installer in order to make life easier for developers. With it you can:

- Choose the version of Laravel to be installed (5-6-7-8)
- Choose which authentication package to install (Laravel / UI or Jetstream)

## How to Install

```shell 

composer global require rubensrocha/lara-install-wizard

```

## Executable Command

 ```shell
larawizard
 ```

## Commands List
 
 |Command                |Description                          |Options                         |  
 |----------------|-------------------------------|-----------------------------|  
 |**`new`**|Create a new Laravel Project           |`name` (name of your project)            |  
 |**`version`**|Choose the Laravel Version to install           |            |  
 |**`--dev`**|Installs the latest "development" release           |            |  
 |**`--jet`**|Installs the Laravel Jetstream scaffolding           |            |  
 |**`--stack`**|The Jetstream stack that should be installed           |`livewire`, `inertia`            |  
 |**`--teams`**|Indicates whether Jetstream should be scaffolded with team support           |            |  
 |**`--auth`**|Installs the Laravel authentication scaffolding           |            |  
 |**`--preset`**|The Laravel/UI preset that should be installed           |`bootstrap`, `vue`, `react`            |  
 |**`--force`**|Forces install even if the directory already exists           |            |
 
### Examples

Laravel (latest version)

```shell

larawizard new ProjectName

```

Laravel **8** (latest version) with Jetstream

```shell 

larawizard new ProjectName 8.* --jet

```

Laravel **8** (latest version) with Jetstream (Inertia)

```shell 

larawizard new ProjectName 8.* --jet --stack=inertia

```

Laravel 7 (latest version)

```shell 

larawizard new ProjectName 7.*

```

Laravel 7 (latest version) with Laravel/UI

```shell 

larawizard new ProjectName 7.* --auth

```

Laravel 7 (latest version) with Laravel/UI (Vue)

```shell 

larawizard new ProjectName 7.* --auth --preset=vue

```

## Official Documentation

Documentation for installing Laravel can be found on the [Laravel website](https://laravel.com/docs#installing-laravel).

## Contributing

If this project is useful for you, remember to rate it with stars. And if you want to contribute by creating new features or making bug fixes, your help is always welcome.

## License

Laravel Installer is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

This package is a modified version of the official installer, which can be found in the official repository at [this link](https://github.com/laravel/installer).
