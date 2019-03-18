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

 