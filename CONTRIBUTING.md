# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/shetabit/chunky).

## Pull Requests

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - Check the code style with ``$ composer check-style`` and fix it with ``$ composer fix-style``.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests. ``$ composer test`` runs the suite, ``$ make test`` runs it inside a container when there is no PHP on your machine.

- **Keep the static analysis green** - Run ``$ composer analyse``. PHPStan has to report no errors at all.

- **Keep the coverage up** - The code coverage of the test suite has to stay above 90%, which the CI pipeline checks. ``$ composer test-coverage`` reports it locally.

- **Do not leave a file open** - Every file the package touches is written through a single handle that it closes again. A test that leaks one is caught by the handle counting tests of `tests/Unit/FileTest.php`.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.


**Happy coding**!
