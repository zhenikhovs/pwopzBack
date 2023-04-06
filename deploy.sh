echo -e "\n### Script starting ###"
cd $1
echo -e "\n\n### Getting changes from repo ###"
git pull
cd ./local
echo -e "\n\n### Migrate up ###"
php ./bin/migrate.php up --config=main
echo -e "\n### Script finished ###"
