update cscart_storefronts set url = 'localhost:8080' where storefront_id=1;
update cscart_settings_objects set value = 'N' where name = 'secure_admin';
update cscart_settings_objects set value = 'N' where name = 'secure_storefront';
