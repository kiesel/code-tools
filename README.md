# code-tools

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