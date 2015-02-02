# code-tools

[![Build Status on TravisCI](https://secure.travis-ci.org/kiesel/code-tools.svg)](http://travis-ci.org/kiesel/code-tools)
[![XP Framework Mdodule](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)

This project aims to provide utilities as well as an OOP API to perform
code analysis in order to improve code quality and catch some easy to
spot errors that can slip into the code.

## Checking class references in namespace'd code
### Usage:

```sh
$ xpcli -cp path/to/repo/src/main/php net.xp_forge.cmd.CheckClassReferences path/to/file
```

### Detected errors
* Using absolute references in use is discouraged (eg. `use \foo\bar`, better: `use foo\bar`)
* Using relative references in inline code (eg. `new foo\bar\Classname()`, better: `new \foo\bar\Classname()`)
* Repeated use of alias for different classes
* Unloadable class (typo?)
* Unknown class (missing use)