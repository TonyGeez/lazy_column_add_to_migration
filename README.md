# Lazy Column Add to Migration

**Lazy Column Add to Migration** is a Laravel package to easily add columns to existing migrations via Artisan commands. It's especially useful when you need to make critical changes to Laravel project tables in challenging environments.

## Installation

You can install the package via Composer:

1. Install via Composer:

    ```bash
    composer require tonygeez/lazy-column-add-to-migration
    ```

2. If you are using Laravel 5.4 or lower (not tested), add the service provider to the `providers` array in `config/app.php`:

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
php artisan table:add {table?} {column?} [--type={type}] [--nullable] [--after={existing_column}] [--default={value}] [--foreign-model={model}]
```

### Command Parameters

- `table`: The name of the table where the column will be added. If not provided, the command will prompt for it.
- `column`: The name of the new column to be added. If not provided, the command will prompt for it.
- `--type`: The type of the column. Supported types include `bigInteger`, `boolean`, `date`, `dateTime`, `decimal`, `enum`, `float`, `foreignId`, `id`, `increments`, `integer`, `json`, `longText`, `string`, `text`, `timestamps`, `unsignedInteger`. If not provided, the command will prompt for it.
- `--nullable`: Makes the column nullable.
- `--after`: Specifies an existing column after which the new column will be added.
- `--default`: Sets a default value for the column.
- `--foreign-model`: Specifies the model class for foreign key relationships. Used with `foreignId` type.

### Example Usage

Add a string column named `new_column` to the `users` table:

```bash
php artisan table:add users new_column --type=string
```

Make the column nullable and place it after the `email` column:

```bash
php artisan table:add users new_column --type=string --nullable --after=email
```

Add a foreign key column referencing the `projects` table:

```bash
php artisan table:add tasks project_id --type=foreignId --foreign-model=App\\Models\\Project
```

Add an enum column:

```bash
php artisan table:add users status --type=enum
```
The command will prompt you to enter the enum values.

### Interactive Mode

If you run the command without specifying all parameters, it will enter an interactive mode, prompting you for the necessary information:

```bash
php artisan table:add
```

This will guide you through the process of adding a new column, asking for the table name, column name, type, and other relevant details.

## Contributing

Contributions are welcome! Please submit a pull request or create an issue to discuss any changes.

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
```


