# Laravel Import

* [Installation](#installation)
* [Usage](#usage)
  * [Defining Importable Models](#defining-importable-models)
  * [Defining Source Map](#defining-source-map)
  * [Supported Methods / Built in logic](#supported-methods--built-in-logic)
    * [Looping](#looping)
    * [Context Functions](#context-functions)
* [Extendability](#extendability)
* [Configuration](#configuration)


This package allows you to do data import into Laravel application.
This package uses json format source-maps where you define mapping between 
source data and expected database structure.

## Running a command

Package uses artisan commands to run the import commands for their
respective source conversion drivers.

```php
 php artisan import:csv source_map_file_name import_data.csv --header-offset=1
 php artisan import:json source_map_file_name import_data.json
 php artisan import:xml source_map_file_name import_data.xml
 php artisan import:database source_map_file_name import_data.csv --prefix=prfx
```

These commands will load source-map file from storage path and will attempt at processing 
passed data to given data.

## Installation

This package is compatible with Laravel 5.5 or higher. You can install the package
via composer using the following command:

```bash
composer require mtcmedia/laravel-import
```

Whilst Laravel 5.4 or below is not officially supported the functionality
should work if the service provider is registered. This can be done by 
just adding the service provider in `config/app.php` file:

```php
'providers' => [
    // ...
    Mtc\Import\Providers\ImportServiceProvider::class,
];
```

## Usage

Import functionality is called by artisan commands for the respective driver.

```php
 php artisan import:csv source_map_file_name import_data.csv --header-offset=1
 php artisan import:json source_map_file_name import_data.json
 php artisan import:xml source_map_file_name import_data.xml
 php artisan import:database source_map_file_name import_data.csv --prefix=prfx
```

### Defining Importable Models
To allow data import on a model you will need to add `Mtc\Import\Traits\Importable` trait to your Eloquent models.
```php
class Item extends Model {
    use Mtc\Import\Traits\Importable;
    ...
``` 
This will provide the additional methods to the models for importing. 

To see what fields are allowed to be imported the package will look for `$importable` attribute on Model. This attribute
should define the columns that can be populated. If the `$importable` field is not defined on the model it will fall back to
using `$fillable` as basis for importable columns. If model does not use `$fillable` attribute it will use the last resort 
of connecting to database and allowing all columns.

It is also possible to define the fallback values on the model columns to ensure the correct data type is set for the attributes
using `$importable_defaults` attribute on model
```php
protected $importable_defaults = [
    'price' => 0,
    'sort_price' => 0,
];
``` 

As for importing related models the package will expect `$importable_relationships` attribute to be present. If this 
attribute will not be set it will not allow importing the relationships even if they are part of the source map.
Importable relationships should follow the below example.
```php
    protected $importable_relationships = [
        'images' => [
            'type' => HasMany::class,
            'model' => Image::class,
        ],
        'custom' => [
            'type' => HasOne::class,
            'model' => Custom::class,
        ],
    ];
```

### Defining Source Map
Source file must be defined before doing import and should follow the outlined example:

```json
{
    "model": "App\\Product",
    "query": {
        "table": "product",
        "relationships": {
            "ProductSupplier": {
                "table": "product_supplier",
                "parent_column": "id_product",
                "child_column": "id_product",
                "relationships": {
                    "Supplier": {
                        "table": "supplier",
                        "parent_column": "id_supplier",
                        "child_column": "id_supplier"
                    }
                }
            },
            "Language": {
                "table": "product_lang",
                "parent_column": "id_product",
                "child_column": "id_product"
            },
            "Image": {
                "table": "image",
                "parent_column": "id_product",
                "child_column": "id_product"
            }
        }
    },
    "data_map": {
        "id": "id_product",
        "reference": "reference",
        "seller_id": "ProductSupplier.Supplier.id_customer",
        "status": "~external(App\\CustomDataProcessor,getLotStatus)",
        "name": "Language.name",
        "description": "Language.description_short",
        "images": [
            {
                "name": "Image.*.~external(App\\CustomDataProcesso,downloadImage,https://www.example.com)",
                "default": "Image.*.cover",
                "order": "Image.*.position"
            }
        ]
    }
}
```

The source map is split into 3 top level objects:

* `model` - defines the data model that will be base for populating data
* `query` - only required on database models. Defines data structure to allow loading data from multiple tables 
without the necessity of creating models for the data on the import side
* `data_map` - mapping between the fields on current application and their respective fields on the import data structure

### Supported methods / built-in logic
Since data rarely transfers from one structure to a different one in one-to-one fashion there are some methods 
that are built in to help with the data migration. 

#### Looping
Large requirement for importing data usually is processing normalized data with children entries.
Rather than creating a separate import map and processing for these child entries this package allows processing looped 
data (e.g. item with multiple images).

By default package uses `*` symbol to identify looped item, however this can be changed in the import config.
When using looped structure the relation will go through all possible entries and create child entries for them.
**NB** This functionality supports only data-fetching from same structure, it will not allow loading data from two different data loops

#### Context Functions
The import engine uses context functions that are simply identified
by using tilde symbol before its name and wrapping attributes in parenthesis resulting in the following format: `~function()`.  
Currently following context functions are supported:

* `~compare(field,compare_value,compare_operator,true_value,false_value)` - allows comparing values.
Checks `field` against `compare_value` using `compare_operator`. Returns `true_value` or `false_value` based on comparison result.
* `~int()` - casts value to integer
* `~float()` - casts value to float
* `~now(format)` - returns `Carbon::now(format)` timestamp. `format` is optional and will use default to string timestamp if omitted.
* `~concat(argument1,argument2)` - will concatenate list of arguments together. Supports unrestricted amount of arguments
* `~external(class_name,method_name,arguments)` - allows passing data to external script to resolve custom mapping.
This when calling method it will pass the `$data` attribute (which holds current data structure) alongside the list of arguments.

## Extendability
Main options for extending the functionality is during the mapping 
process. This can be done by mapping field against the `~external()`
method that allows calling methods for parsing data values.

## Configuration
This package offers 2 config files (`database.php` and `import.php`).

`database.php` extends the main database config by adding a new connection
for import source. This source is used for database import flow.

`import.php` offers configuration options for the package like
changing the import file paths in storage directory, defining db chunk
sizes or changing the loop operator symbol.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please email [opensource@mtcmedia.co.uk](mailto:opensource@mtcmedia.co.uk) instead of using the issue tracker.

## License

The GNU General Purpose License (GPL). Please see [License File](LICENSE) for more information.