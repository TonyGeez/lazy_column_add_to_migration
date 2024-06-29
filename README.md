# Lazy Column Add to Migration

**Lazy Column Add to Migration** is a Laravel package to easily add columns to existing migrations via Artisan commands as i sadly had to make so critical change to Laravel projects tables on a iPhone and it was a real pain in the ... 🙂‍↕️

## Installation

You can install the package via Composer:

1. Add the package repository to your `composer.json`:

    ```json
    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/TonyGeez/lazy_column_add_to_migration"
            }
        ],
        "require": {
            "tonygeez/lazy-column-add-to-migration": "dev-master"
        }
    }
    ```

2. Run the `composer require` command:

    ```bash
    composer require tonygeez/lazy-column-add-to-migration
    ```

3. If you are using Laravel 5.4 or lower, add the service provider to the `providers` array in `config/app.php`:

    ```php
    'providers' => [
        // Other service providers...

        TonyGeez\LazyColumnAddToMigration\LazyColumnServiceProvider::class,
    ];
    ```

    For Laravel 5.5 and above, the package will be auto-discovered.

## Usage

### Add a Column to a Table

The main functionality of this package is to add a new column to an existing table via a simple Artisan command.

You can use the `table:add` command as follows:

    ```bash
    php artisan table:add {table} {column} --type={type} [--nullable] [--after={existing_column}]
    ```

### Command Parameters

- `table`: The name of the table where the column will be added. If not provided, the command will prompt for it.
- `column`: The name of the new column to be added. If not provided, the command will prompt for it.
- `--type`: The type of the column. Supported types include `integer`, `string`, `boolean`, `date`, `text`, `bigInteger`, `decimal`. If not provided, the command will prompt for it.
- `--nullable`: Makes the column nullable.
- `--after`: Specifies an existing column after which the new column will be added. If not provided, the command will prompt for it.

### Example Usage

Add a string column named `new_column` to the `users` table:

    ```bash
    php artisan table:add users new_column --type=string
    ```

Make the column nullable and place it after the `email` column:

    ```bash
    php artisan table:add users new_column --type=string --nullable --after=email
    ```

### List Table Columns

To list all columns of a specific table, use the `table:columns` command:

    ```bash
    php artisan table:columns {table}
    ```

This will output all the columns of the specified table.

## Contributing

Contributions are welcome! Please submit a pull request or create an issue to discuss any changes.

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
