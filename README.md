Sigils go brrrrrr API

The sigils go brrrrrr API is a collaborative e-commerce API where every user can create and update products.

Carts and orders are linked to users.

AUTH routes require the token obtained via POST /api/login to be passed via the authorization header.

All bodies and responses are JSON.

POST /api/register
Given a body, this endpoint registers a user. All information are mandatory, missing one will return an error.

Body formdata:
-login
-email
-password
-firstname
-lastname

POST /api/login
Users can obtain a token by sending their email and password to this endpoint.
This token will need to be sent via the Authorization header to every endpoint markes with AUTH.

Body formdata:
-email
-password

GET /api/user AUTH
Display current user information

PUT /api/user AUTH
Update the current user information if the current password is provided as passwordVerification. All information are optional. The route will only update the information it receives.

Body formdata:
-login
-email
-password
-passwordVerification
-firstname
-lastname

DELETE /api/user AUTH
Delete current user if the correct password is provided as passwordVerification.

Body formdata:
-passwordVerification

POST /api/product AUTH
Create a product. All informations are mandatory. the photo needs to be a link to an image.

Body formdata:
-name
-description
-photo
-price

GET /api/products
Get a list of all products.

GET /api/product/{productId}
Get the information of the product whose id has been passed in the url.

PUT /api/product/{productId} AUTH
Update the product whose id has been passed in the url. All information are optional. The route will only update the information it receives.

Body formdata:
-name
-description
-photo
-price


DELETE /api/product/{productId} AUTH
Delete the product whose id has been passed in the url.

PUT /api/cart/{productId} AUTH
Update your cart by adding one instance of the product whose id has been passed in the url.
If you don't have an existing cart, it creates one before adding the product.

DELETE /api/cart/{productId} AUTH
Remove one instance of the product whose id has been passed in the url from your cart.

GET /api/cart AUTH
Get the current content of your cart.

DELETE /api/cart AUTH
Delete your cart.

POST /api/cart/validate AUTH
Create an order for all items in your cart. Destroys your cart.

GET /api/orders AUTH
Get all your orders.

DELETE /api/order AUTH
Delete all your orders.

GET /api/order/{orderId} AUTH
Get the order whose id has been passed in the url.

DELETE /api/order/{orderId} AUTH
Delete the order whose id has been passed in the url.
