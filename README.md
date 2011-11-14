It's DataMapper to map objects into storages: MySQL or Redis.      

Main classes are:    
**Mapper.php** - basic mapper, which can use:    
  **Redis\Gateway.php** - gateway to Redis storage    
  or  
  **MySQL\Gateway.php** - gateway to MySQL  

To understand, how it works, read about patterns: [Data Mapper](http://martinfowler.com/eaaCatalog/dataMapper.html) and [Table Data Gateway](http://martinfowler.com/eaaCatalog/tableDataGateway.html)  

Gateways uses MetaTable to map schema of your table, this schema can be fetched from existing database, or imported by array, or constructed directly in code.