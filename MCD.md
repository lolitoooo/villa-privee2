# Modèle Conceptuel de Données (MCD) - Villa Privée

```
+----------------+     POSSÈDE    +----------------+
|     USER       |<------------->>|     VILLA      |
+----------------+               +----------------+
| id            |     1,N        | id            |
| email         |               | title         |
| password      |               | description   |
| firstname     |               | price         |
| lastname      |               | location      |
| roles         |               | bedrooms      |
| is_banned     |               | bathrooms     |
| create_at     |               | capacity      |
| update_at     |               | slug          |
| is_verified   |               | is_active     |
| reset_token   |               | created_at    |
+----------------+               | updated_at    |
        ^                       +----------------+
        |                              ^
        |                              |
    ÉCRIT                         CONTIENT
        |                              |
        |                              |
        | 0,N                        | 1,N
+----------------+               +----------------+
| VILLA_REVIEW   |               | VILLA_IMAGE   |
+----------------+               +----------------+
| id            |               | id            |
| content       |               | filename      |
| rating        |               | uploaded_at   |
| created_at    |               +----------------+
+----------------+

+----------------+     RÉSERVE    +----------------+     GÉNÈRE     +----------------+
|     USER       |<------------->>|  RESERVATION   |<------------->|    INVOICE     |
+----------------+     0,N        +----------------+     0,1       +----------------+
                                 | id            |               | id            |
                                 | start_date    |               | filename      |
                                 | end_date      |               | number        |
                                 | total_price   |               | created_at    |
                                 | status        |               +----------------+
                                 | payment_id    |
                                 | invoice_num   |
                                 | created_at    |
                                 +----------------+

+----------------+    FAVORIS    +----------------+
|     USER       |<------------->|     VILLA      |
+----------------+    0,N        +----------------+
                     0,N
```