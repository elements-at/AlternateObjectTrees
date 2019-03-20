# Alternate Object Trees
Plugin for defining alternate object trees based on object attributes. 
 
 
### Configuration
![Config](doc/img/config.png)

 
### Visualisation in Frontend
![visualisation](doc/img/visualisation.png) 

  
### Permissions
![permissions](doc/img/permissions.png)

### Custom Tree Builder
If you specifiy a custom tree builder class in your tree config, such as
```php
\AppBundle\Util\Backend\DynamicTree\VirtualProductTreeBuilder
```
then it is possible to combine multiple object (types) per child node, by overriding
the ``buildCustomTree`` method, just as in ```DefaultTreeBuilder.php````.

### Upgrade Notes

#### Upgrade from v2.0.0
If you want to use the Custom Tree Builder, please add
the field "customTreeBuilderClass" as varchar(255) in the bundle's database table (compare installer).
Also make sure to clear the data cache.