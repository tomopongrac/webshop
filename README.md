# Installation

1. Clone this repository `git clone`
2. Update DATABASE_URL in .env.local file with your database credentials
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/webshop"
```
2. Create database with the following command
```
php ./bin/console doctrine:database:create
```

# How to run static analysis
1. Run `composer phpstan`

# How to run code style fixer
1. Run `composer cs-fixer-fix`
2. Run `composer cs-fixer-check` to check if there are any code style issues

# How to add composer command to git hooks
1. Navigate to the .git/hooks directory
2. Create a new file named `pre-commit`
3. Add the following content to the file:
```bash
#!/bin/sh
composer cs-check
composer phpstan
```
4. Make the file executable by running `chmod +x pre-commit`
If you want to skip the pre-commit hook, you can run `git commit --no-verify`
Or if you want to run composer tasks after the commit is done, follow the same steps but name the fiel as `post-commit` instead of `pre-commit`