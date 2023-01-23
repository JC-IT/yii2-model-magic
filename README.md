# Models for forms and search for Yii2

[![codecov](https://codecov.io/gh/jc-it/yii2-model-magic/branch/master/graph/badge.svg)](https://codecov.io/gh/jc-it/yii2-model-magic)
[![Continous integration](https://github.com/jc-it/yii2-model-magic/actions/workflows/ci.yaml/badge.svg)](https://github.com/jc-it/yii2-model-magic/actions/workflows/ci.yaml)
![Packagist Total Downloads](https://img.shields.io/packagist/dt/jc-it/yii2-model-magic)
![Packagist Monthly Downloads](https://img.shields.io/packagist/dm/jc-it/yii2-model-magic)
![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/jc-it/yii2-model-magic)
![Packagist Version](https://img.shields.io/packagist/v/jc-it/yii2-model-magic)

This extension provides a package that implements some extra models with basic functionality for working with forms and search models.

```bash
$ composer require jc-it/yii2-model-magic
```

or add

```
"jc-it/yii2-model-magic": "^<latest version>"
```

to the `require` section of your `composer.json` file.

## Configuration
To use the models, just extend these in your own models.

## TODO
- Fix PHPStan, re-add to `captainhook.json`
  - ```      
    {
        "action": "vendor/bin/phpstan",
        "options": [],
        "conditions": []
    },
    ```
- Add tests

## Credits
- [Joey Claessen](https://github.com/joester89)

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/jc-it/yii2-model-magic/blob/master/LICENSE) for more information.
