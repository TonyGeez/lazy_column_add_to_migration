# Lazy Column Add to Migration

**Lazy Column Add to Migration** This simple Laravel package provides Artisan commands to easily add columns to existing migrations. It is the result of a recent experience where I had to make a critical change to the project table. At that time, the only option I had was my iPhone with SSH access to the project files. This highlighted the lack of native Laravel artisan commands for such tasks, which are not only straightforward to implement but also simplify the development process in limited environments and for beginners.

## Installation

You can install the package via Composer:

```bash
composer require tonygeez/lazy-column-add-to-migration
```

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


