# PrintPandora — E-Commerce REST API Documentation

> **Base URL:** `http://localhost:8000/api`
> **Content-Type:** `application/json`
> **Accept Header:** `application/json`

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Categories](#2-categories)
3. [Products](#3-products)
4. [Cart](#4-cart)
5. [Orders](#5-orders)
6. [User / Account](#6-user--account)
7. [Wishlist](#7-wishlist)
8. [Coupons / Discounts](#8-coupons--discounts)
9. [Reviews](#9-reviews)
10. [Blog](#10-blog)
11. [Affiliate Program](#11-affiliate-program)
12. [Pagination & Conventions](#12-pagination--conventions)

---

## Standard Response Format

All responses follow this structure:

```json
{
  "success": true,
  "message": "Operation description",
  "data": { }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": { }
}
```

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200  | OK — Request succeeded |
| 201  | Created — Resource created |
| 400  | Bad Request — Invalid input |
| 401  | Unauthorized — Missing or invalid token |
| 403  | Forbidden — Insufficient permissions |
| 404  | Not Found — Resource doesn't exist |
| 422  | Unprocessable Entity — Validation error |
| 500  | Internal Server Error |

---

## 1. Authentication

### 1.1 Register

```
POST /register
```

**Body:**

| Field       | Type    | Required | Description              |
|-------------|---------|----------|--------------------------|
| first_name  | string  | ✅        | First name               |
| last_name   | string  | ✅        | Last name                |
| email       | string  | ✅        | Unique email address     |
| password    | string  | ✅        | Min 8 characters         |
| country     | string  | ✅        | Country code (e.g. "US") |
| subscribe   | boolean | —        | Subscribe to newsletter (default: false) |

**Response `201`:**

```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "country": "US",
      "subscribe": false
    },
    "token": "1|abc123def456..."
  }
}
```

---

### 1.2 Login

```
POST /login
```

**Body:**

| Field    | Type   | Required |
|----------|--------|----------|
| email    | string | ✅        |
| password | string | ✅        |

**Response `200`:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123def456..."
  }
}
```

---

### 1.3 Logout

```
POST /logout
```

**Headers:** `Authorization: Bearer {token}`

**Response `200`:**

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 1.4 Get Current User

```
GET /user
```

**Headers:** `Authorization: Bearer {token}`

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "avatar": "https://...",
    "created_at": "2026-01-15T10:30:00Z"
  }
}
```

---

### 1.5 Forgot Password

```
POST /forgot-password
```

**Body:**

| Field | Type   | Required | Description          |
|-------|--------|----------|----------------------|
| email | string | ✅        | User's email address |

**Response `200`:**

```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

---

## 2. Categories

### 2.1 List All Categories

```
GET /categories
```

**Query Params:**

| Param     | Type   | Default | Description                                      |
|-----------|--------|---------|--------------------------------------------------|
| parent    | int    | —       | Filter by parent category ID (0 for root)        |
| tree      | boolean| false   | Return categories as a nested tree structure     |
| per_page  | int    | 15      | Items per page (max 100, ignored when tree=true) |
| page      | int    | 1       | Page number                                      |

**Response `200` (flat, default):**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "T-Shirts",
        "slug": "t-shirts",
        "description": "Custom printed t-shirts",
        "image": "https://...",
        "parent_id": null,
        "product_count": 24,
        "is_active": true,
        "children_count": 3
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 5
    }
  }
}
```

**Response `200` (tree, `?tree=true`):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "T-Shirts",
      "slug": "t-shirts",
      "description": "Custom printed t-shirts",
      "image": "https://...",
      "parent_id": null,
      "product_count": 24,
      "is_active": true,
      "children": [
        {
          "id": 10,
          "name": "V-Neck T-Shirts",
          "slug": "v-neck-t-shirts",
          "description": "V-neck style t-shirts",
          "image": "https://...",
          "parent_id": 1,
          "product_count": 8,
          "is_active": true,
          "children": []
        },
        {
          "id": 11,
          "name": "Crew Neck T-Shirts",
          "slug": "crew-neck-t-shirts",
          "description": "Crew neck style t-shirts",
          "image": "https://...",
          "parent_id": 1,
          "product_count": 12,
          "is_active": true,
          "children": []
        }
      ]
    }
  ]
}
```

---

### 2.2 Get Single Category

```
GET /categories/{slug}
```

**Query Params:**

| Param    | Type    | Default | Description                        |
|----------|---------|---------|------------------------------------|
| with     | string  | —       | Eager load: `children`, `parent`, `children,parent` |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "V-Neck T-Shirts",
    "slug": "v-neck-t-shirts",
    "description": "V-neck style t-shirts",
    "image": "https://...",
    "parent_id": 1,
    "product_count": 8,
    "is_active": true,
    "parent": {
      "id": 1,
      "name": "T-Shirts",
      "slug": "t-shirts"
    },
    "children": [],
    "breadcrumbs": [
      { "name": "T-Shirts", "slug": "t-shirts" },
      { "name": "V-Neck T-Shirts", "slug": "v-neck-t-shirts" }
    ]
  }
}
```

**Response `404`:**

```json
{
  "success": false,
  "message": "Category not found"
}
```

---

### 2.3 Get Category Tree

```
GET /categories/tree
```

Returns the full category hierarchy as a nested tree. Only returns active categories by default.

**Query Params:**

| Param     | Type    | Default | Description                     |
|-----------|---------|---------|---------------------------------|
| active_only | boolean | true  | Only include active categories  |

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "T-Shirts",
      "slug": "t-shirts",
      "image": "https://...",
      "product_count": 24,
      "is_active": true,
      "children": [
        {
          "id": 10,
          "name": "V-Neck T-Shirts",
          "slug": "v-neck-t-shirts",
          "image": "https://...",
          "product_count": 8,
          "is_active": true,
          "children": []
        }
      ]
    },
    {
      "id": 2,
      "name": "Hoodies",
      "slug": "hoodies",
      "image": "https://...",
      "product_count": 18,
      "is_active": true,
      "children": []
    }
  ]
}
```

---

## 3. Products

### 3.1 List Products

```
GET /products
```

**Query Params:**

| Param      | Type   | Default | Description                            |
|------------|--------|---------|----------------------------------------|
| category   | string | —       | Filter by category slug                |
| search     | string | —       | Search by name or description          |
| min_price  | number | —       | Minimum price filter                   |
| max_price  | number | —       | Maximum price filter                   |
| sort       | string | latest  | Sort: `latest`, `price_asc`, `price_desc`, `name`, `popular` |
| per_page   | int    | 15      | Items per page (max 100)               |
| page       | int    | 1       | Page number                            |

**Example:**

```
GET /products?category=t-shirts&min_price=10&max_price=50&sort=price_asc
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Classic White Tee",
        "slug": "classic-white-tee",
        "description": "Premium cotton t-shirt with custom print",
        "price": 29.99,
        "sale_price": 24.99,
        "stock": 150,
        "sku": "TS-001",
        "image": "https://...",
        "images": [
          "https://...front.jpg",
          "https://...back.jpg"
        ],
        "category": {
          "id": 1,
          "name": "T-Shirts",
          "slug": "t-shirts"
        },
        "avg_rating": 4.5,
        "review_count": 32,
        "is_active": true,
        "created_at": "2026-03-01T12:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42
    },
    "filters": {
      "category": "t-shirts",
      "min_price": 10,
      "max_price": 50,
      "sort": "price_asc"
    }
  }
}
```

---

### 3.2 Get Single Product

```
GET /products/{slug}
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Classic White Tee",
    "slug": "classic-white-tee",
    "description": "Premium cotton t-shirt with custom print area",
    "price": 29.99,
    "sale_price": 24.99,
    "stock": 150,
    "sku": "TS-001",
    "image": "https://...",
    "images": [
      "https://...front.jpg",
      "https://...back.jpg",
      "https://...mockup.jpg"
    ],
    "category": {
      "id": 1,
      "name": "T-Shirts",
      "slug": "t-shirts"
    },
    "variants": [
      {
        "id": 1,
        "name": "Size",
        "options": ["S", "M", "L", "XL", "XXL"]
      },
      {
        "id": 2,
        "name": "Color",
        "options": ["White", "Black", "Navy"]
      }
    ],
    "avg_rating": 4.5,
    "review_count": 32,
    "related_products": [
      {
        "id": 5,
        "name": "V-Neck Tee",
        "slug": "v-neck-tee",
        "price": 27.99,
        "image": "https://..."
      }
    ],
    "is_active": true,
    "created_at": "2026-03-01T12:00:00Z"
  }
}
```

---

### 3.3 Get Featured Products

```
GET /products/featured
```

**Query Params:**

| Param    | Type | Default |
|----------|------|---------|
| limit    | int  | 8       |

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Classic White Tee",
      "slug": "classic-white-tee",
      "price": 29.99,
      "sale_price": 24.99,
      "image": "https://..."
    }
  ]
}
```

---

## 4. Cart

> All cart endpoints require authentication: `Authorization: Bearer {token}`

### 4.1 Get Cart

```
GET /cart
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Classic White Tee",
          "slug": "classic-white-tee",
          "image": "https://...",
          "price": 29.99,
          "sale_price": 24.99,
          "stock": 150
        },
        "variant": "M / White",
        "quantity": 2,
        "unit_price": 24.99,
        "subtotal": 49.98
      }
    ],
    "summary": {
      "item_count": 2,
      "subtotal": 49.98,
      "tax": 4.50,
      "shipping": 5.99,
      "discount": 0,
      "total": 60.47
    }
  }
}
```

---

### 4.2 Add Item to Cart

```
POST /cart
```

**Body:**

| Field     | Type   | Required | Description                    |
|-----------|--------|----------|--------------------------------|
| product_id| int    | ✅        | Product ID                     |
| quantity  | int    | ✅        | Quantity (min 1)               |
| variant   | string | —        | Variant selection (e.g. "M / White") |
| customization | object | —    | Custom print/design data       |

**Example:**

```json
{
  "product_id": 1,
  "quantity": 2,
  "variant": "M / White",
  "customization": {
    "design_url": "https://storage.../design.png",
    "position": "center"
  }
}
```

**Response `201`:**

```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "item_count": 3
  }
}
```

---

### 4.3 Update Cart Item

```
PATCH /cart/{item}
```

**Body:**

| Field    | Type | Required |
|----------|------|----------|
| quantity | int  | ✅        |

**Response `200`:**

```json
{
  "success": true,
  "message": "Cart item updated"
}
```

---

### 4.4 Remove Item from Cart

```
DELETE /cart/{item}
```

**Response `200`:**

```json
{
  "success": true,
  "message": "Item removed from cart"
}
```

---

### 4.5 Clear Cart

```
DELETE /cart
```

**Response `200`:**

```json
{
  "success": true,
  "message": "Cart cleared"
}
```

---

## 5. Orders

> All order endpoints require authentication: `Authorization: Bearer {token}`

### 5.1 Create Order (Checkout)

```
POST /orders
```

**Body:**

| Field             | Type   | Required | Description                          |
|-------------------|--------|----------|--------------------------------------|
| shipping_name     | string | ✅        | Recipient full name                  |
| shipping_email    | string | ✅        | Recipient email                      |
| shipping_phone    | string | —        | Recipient phone                      |
| shipping_address  | string | ✅        | Street address                       |
| shipping_city     | string | ✅        | City                                 |
| shipping_state    | string | —        | State / Province                     |
| shipping_zip      | string | ✅        | Postal / ZIP code                    |
| shipping_country  | string | ✅        | Country code (e.g. "US")             |
| payment_method    | string | ✅        | `stripe`, `paypal`, `cod`            |
| coupon_code       | string | —        | Discount coupon code                 |
| notes             | string | —        | Order notes                          |

**Example:**

```json
{
  "shipping_name": "John Doe",
  "shipping_email": "john@example.com",
  "shipping_phone": "+1234567890",
  "shipping_address": "123 Main Street",
  "shipping_city": "New York",
  "shipping_state": "NY",
  "shipping_zip": "10001",
  "shipping_country": "US",
  "payment_method": "stripe",
  "coupon_code": "SAVE10",
  "notes": "Please gift wrap"
}
```

**Response `201`:**

```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "PP-20260603-0001",
      "status": "pending",
      "total": 54.47,
      "payment_status": "pending",
      "payment_method": "stripe",
      "payment_url": "https://checkout.stripe.com/...",
      "items": [
        {
          "product_name": "Classic White Tee",
          "variant": "M / White",
          "quantity": 2,
          "unit_price": 24.99,
          "subtotal": 49.98
        }
      ],
      "shipping": {
        "name": "John Doe",
        "address": "123 Main Street",
        "city": "New York",
        "state": "NY",
        "zip": "10001",
        "country": "US"
      },
      "created_at": "2026-06-03T14:30:00Z"
    }
  }
}
```

---

### 5.2 List User Orders

```
GET /orders
```

**Query Params:**

| Param    | Type   | Default | Description                                    |
|----------|--------|---------|------------------------------------------------|
| status   | string | —       | Filter: `pending`, `processing`, `shipped`, `delivered`, `cancelled` |
| per_page | int    | 15      | Items per page                                 |
| page     | int    | 1       | Page number                                    |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "order_number": "PP-20260603-0001",
        "status": "pending",
        "total": 54.47,
        "payment_status": "paid",
        "item_count": 2,
        "created_at": "2026-06-03T14:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

---

### 5.3 Get Order Details

```
GET /orders/{order}
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "PP-20260603-0001",
    "status": "shipped",
    "subtotal": 49.98,
    "tax": 4.50,
    "shipping_cost": 5.99,
    "discount": 5.00,
    "total": 55.47,
    "payment_method": "stripe",
    "payment_status": "paid",
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Classic White Tee",
          "slug": "classic-white-tee",
          "image": "https://..."
        },
        "variant": "M / White",
        "quantity": 2,
        "unit_price": 24.99,
        "subtotal": 49.98
      }
    ],
    "shipping": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "address": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "zip": "10001",
      "country": "US"
    },
    "tracking": {
      "carrier": "USPS",
      "tracking_number": "9400111899223100001",
      "tracking_url": "https://tools.usps.com/go/TrackConfirmAction?tLabels=..."
    },
    "notes": "Please gift wrap",
    "created_at": "2026-06-03T14:30:00Z",
    "updated_at": "2026-06-05T09:00:00Z"
  }
}
```

---

### 5.4 Cancel Order

```
POST /orders/{order}/cancel
```

**Body:**

| Field  | Type   | Required | Description         |
|--------|--------|----------|---------------------|
| reason | string | —        | Cancellation reason |

**Response `200`:**

```json
{
  "success": true,
  "message": "Order cancelled successfully"
}
```

---

### 5.5 Download Receipt

```
GET /orders/{order}/receipt
```

Download a PDF receipt for the specified order. Requires authentication and the order must belong to the authenticated user.

**Query Params:**

| Param  | Type   | Default | Description                        |
|--------|--------|---------|------------------------------------|
| format | string | pdf     | Receipt format: `pdf`, `html`      |

**Response `200`:**

Returns the receipt file with appropriate `Content-Type` and `Content-Disposition` headers.

```
Content-Type: application/pdf
Content-Disposition: attachment; filename="receipt-PP-20260603-0001.pdf"
```

**Response `404`:**

```json
{
  "success": false,
  "message": "Order not found"
}
```

**Response `422` (order not eligible):**

```json
{
  "success": false,
  "message": "Receipt is not available for this order"
}
```

---

### 5.6 Submit Corporate Order Quote

```
POST /corporate-quotes
```

Submit a corporate/bulk order quote request. This endpoint does not require authentication.

**Content-Type:** `multipart/form-data`

**Body:**

| Field                | Type   | Required | Description                                              |
|----------------------|--------|----------|----------------------------------------------------------|
| first_name           | string | ✅        | Contact first name                                       |
| last_name            | string | ✅        | Contact last name                                        |
| email                | string | ✅        | Contact email address                                    |
| phone                | string | ✅        | Contact phone number                                     |
| job_title            | string | ✅        | Job title / position                                     |
| company_name         | string | ✅        | Company name                                             |
| address              | string | ✅        | Street address                                           |
| city                 | string | ✅        | City                                                     |
| country              | string | ✅        | Country code (e.g. "US")                                 |
| zip_code             | string | ✅        | ZIP / Postal code                                        |
| number_of_sets       | int    | ✅        | Number of sets/employees (1–10)                          |
| number_of_cards      | int    | ✅        | Number of cards: `100`, `250`, `500`, or `1000`          |
| products_interested  | string | ✅        | Products/features of interest (free text)                |
| found_us             | string | ✅        | How did you find us: `google`, `social_media`, `sample_pack`, `referral`, `trade_show`, `other` |
| design_file          | file   | ✅        | Design file upload (PDF, AI, PSD, PNG, JPG — max 10MB)   |

**Example (multipart form):**

```
first_name: "John"
last_name: "Doe"
email: "john@acme.com"
phone: "+1234567890"
job_title: "Marketing Director"
company_name: "Acme Corp"
address: "123 Business Ave"
city: "New York"
country: "US"
zip_code: "10001"
number_of_sets: 5
number_of_cards: 500
products_interested: "Premium business cards with matte finish and spot UV coating for our executive team."
found_us: "google"
design_file: [binary file data]
```

**Response `201`:**

```json
{
  "success": true,
  "message": "Your corporate quote request has been submitted successfully. Our team will contact you within 1-2 business days.",
  "data": {
    "id": 1,
    "reference_number": "CQ-20260604-0001",
    "status": "pending",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@acme.com",
    "company_name": "Acme Corp",
    "number_of_sets": 5,
    "number_of_cards": 500,
    "created_at": "2026-06-04T14:30:00Z"
  }
}
```

**Response `422` (validation error):**

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field must be a valid email address."],
    "number_of_sets": ["The number of sets must be between 1 and 10."],
    "number_of_cards": ["The selected number of cards is invalid."],
    "design_file": ["The design file must be a file of type: pdf, ai, psd, png, jpg, jpeg."]
  }
}
```

---

## 6. User / Account

> All endpoints require authentication: `Authorization: Bearer {token}`

### 6.1 User Dashboard

```
GET /user/dashboard
```

Returns the authenticated user's profile and recent orders.

**Query Params:**

| Param          | Type | Default | Description                    |
|----------------|------|---------|--------------------------------|
| recent_orders  | int  | 5       | Number of recent orders to return |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+1234567890",
      "avatar": "https://...",
      "country": "US",
      "subscribe": false,
      "created_at": "2026-01-15T10:30:00Z"
    },
    "recent_orders": [
      {
        "id": 1,
        "order_number": "PP-20260603-0001",
        "status": "pending",
        "total": 54.47,
        "payment_status": "paid",
        "item_count": 2,
        "created_at": "2026-06-03T14:30:00Z"
      }
    ],
    "stats": {
      "total_orders": 12,
      "total_spent": 540.30
    }
  }
}
```

---

### 6.2 Update Profile

```
PATCH /user/profile
```

**Body:**

| Field       | Type    | Required |
|-------------|---------|----------|
| first_name  | string  | —        |
| last_name   | string  | —        |
| phone       | string  | —        |
| country     | string  | —        |
| subscribe   | boolean | —        |
| avatar      | file    | —        |

**Response `200`:**

```json
{
  "success": true,
  "message": "Profile updated",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "country": "US",
    "subscribe": true
  }
}
```

---

### 6.3 Change Password

```
PUT /user/password
```

**Body:**

| Field           | Type   | Required |
|-----------------|--------|----------|
| current_password| string | ✅        |
| password        | string | ✅        |
| password_confirmation | string | ✅  |

**Response `200`:**

```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

### 6.4 List Addresses

```
GET /addresses
```

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "label": "Home",
      "name": "John Doe",
      "phone": "+1234567890",
      "address": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "zip": "10001",
      "country": "US",
      "is_default": true
    }
  ]
}
```

---

### 6.5 Add Address

```
POST /addresses
```

**Body:**

| Field      | Type    | Required |
|------------|---------|----------|
| label      | string  | ✅        |
| name       | string  | ✅        |
| phone      | string  | —        |
| address    | string  | ✅        |
| city       | string  | ✅        |
| state      | string  | —        |
| zip        | string  | ✅        |
| country    | string  | ✅        |
| is_default | boolean | —        |

**Response `201`:**

```json
{
  "success": true,
  "message": "Address added",
  "data": { "id": 2 }
}
```

---

### 6.6 Update Address

```
PATCH /addresses/{address}
```

Same body as Add Address. **Response `200`.**

---

### 6.7 Delete Address

```
DELETE /addresses/{address}
```

**Response `200`.**

---

## 7. Wishlist

> All endpoints require authentication: `Authorization: Bearer {token}`

### 7.1 Get Wishlist

```
GET /wishlist
```

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Classic White Tee",
        "slug": "classic-white-tee",
        "price": 29.99,
        "sale_price": 24.99,
        "image": "https://...",
        "stock": 150
      },
      "added_at": "2026-05-20T10:00:00Z"
    }
  ]
}
```

---

### 7.2 Add to Wishlist

```
POST /wishlist
```

**Body:**

| Field      | Type | Required |
|------------|------|----------|
| product_id | int  | ✅        |

**Response `201`:**

```json
{
  "success": true,
  "message": "Added to wishlist"
}
```

---

### 7.3 Remove from Wishlist

```
DELETE /wishlist/{product}
```

**Response `200`.**

---

## 8. Coupons / Discounts

### 8.1 Validate Coupon

```
POST /coupons/apply
```

**Body:**

| Field | Type   | Required |
|-------|--------|----------|
| code  | string | ✅        |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "code": "SAVE10",
    "type": "percentage",
    "value": 10,
    "description": "10% off your order",
    "min_order": 20.00,
    "expires_at": "2026-12-31T23:59:59Z"
  }
}
```

**Response `422` (invalid/expired):**

```json
{
  "success": false,
  "message": "Coupon code is expired"
}
```

---

## 9. Reviews

### 9.1 List Product Reviews

```
GET /products/{slug}/reviews
```

**Query Params:**

| Param    | Type   | Default |
|----------|--------|---------|
| sort     | string | latest  | `latest`, `highest`, `lowest` |
| per_page | int    | 15      |
| page     | int    | 1       |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "user": {
          "id": 5,
          "name": "Jane S."
        },
        "rating": 5,
        "title": "Love it!",
        "comment": "Great quality print, fits perfectly.",
        "created_at": "2026-05-15T08:00:00Z"
      }
    ],
    "summary": {
      "average": 4.5,
      "total": 32,
      "distribution": {
        "5": 18,
        "4": 10,
        "3": 3,
        "2": 1,
        "1": 0
      }
    },
    "meta": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 32
    }
  }
}
```

---

### 9.2 Submit a Review

```
POST /products/{slug}/reviews
```

> Requires authentication. User must have purchased the product.

**Body:**

| Field   | Type   | Required | Description      |
|---------|--------|----------|------------------|
| rating  | int    | ✅        | 1 to 5           |
| title   | string | ✅        | Review title     |
| comment | string | —        | Review body      |

**Response `201`:**

```json
{
  "success": true,
  "message": "Review submitted successfully"
}
```

---

## 10. Blog

### 10.1 List Blog Posts

```
GET /blog
```

**Query Params:**

| Param      | Type   | Default | Description                                      |
|------------|--------|---------|--------------------------------------------------|
| category   | string | —       | Filter by category slug                          |
| tag        | string | —       | Filter by tag slug                               |
| search     | string | —       | Search by title or excerpt                       |
| sort       | string | latest  | Sort: `latest`, `oldest`, `popular`, `title`     |
| per_page   | int    | 12      | Items per page (max 100)                         |
| page       | int    | 1       | Page number                                      |

**Example:**

```
GET /blog?category=design-tips&sort=popular
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "title": "10 Creative T-Shirt Design Ideas for 2026",
        "slug": "10-creative-tshirt-design-ideas-2026",
        "excerpt": "Discover the latest trends in custom t-shirt printing and get inspired for your next project.",
        "featured_image": "https://...",
        "author": {
          "name": "Sarah Chen",
          "avatar": "https://..."
        },
        "category": {
          "name": "Design Tips",
          "slug": "design-tips"
        },
        "tags": ["t-shirts", "design", "trends"],
        "read_time": 5,
        "view_count": 1240,
        "published_at": "2026-05-20T10:00:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 4,
      "per_page": 12,
      "total": 45
    }
  }
}
```

---

### 10.2 Get Single Blog Post

```
GET /blog/{slug}
```

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "10 Creative T-Shirt Design Ideas for 2026",
    "slug": "10-creative-tshirt-design-ideas-2026",
    "excerpt": "Discover the latest trends in custom t-shirt printing and get inspired for your next project.",
    "content": "<p>Full HTML content of the blog post...</p>",
    "featured_image": "https://...",
    "gallery": [
      "https://...img1.jpg",
      "https://...img2.jpg"
    ],
    "author": {
      "name": "Sarah Chen",
      "avatar": "https://...",
      "bio": "Graphic designer and print enthusiast"
    },
    "category": {
      "name": "Design Tips",
      "slug": "design-tips"
    },
    "tags": ["t-shirts", "design", "trends"],
    "read_time": 5,
    "view_count": 1241,
    "meta_title": "10 Creative T-Shirt Design Ideas for 2026 | PrintPandora",
    "meta_description": "Explore the top t-shirt design trends...",
    "published_at": "2026-05-20T10:00:00Z",
    "updated_at": "2026-05-22T14:00:00Z"
  }
}
```

**Response `404`:**

```json
{
  "success": false,
  "message": "Blog post not found"
}
```

---

### 10.3 List Blog Categories

```
GET /blog/categories
```

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Design Tips",
      "slug": "design-tips",
      "description": "Tips and tricks for creating great prints",
      "post_count": 12
    },
    {
      "id": 2,
      "name": "Product Updates",
      "slug": "product-updates",
      "description": "New products and features",
      "post_count": 8
    }
  ]
}
```

---

### 10.4 List Blog Tags

```
GET /blog/tags
```

**Query Params:**

| Param    | Type | Default |
|----------|------|---------|
| per_page | int  | 50      |

**Response `200`:**

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "t-shirts", "slug": "t-shirts", "post_count": 15 },
    { "id": 2, "name": "design", "slug": "design", "post_count": 22 },
    { "id": 3, "name": "trends", "slug": "trends", "post_count": 9 }
  ]
}
```

---

### 10.5 Get Posts by Author

```
GET /blog/authors/{authorId}
```

**Query Params:**

| Param    | Type | Default |
|----------|------|---------|
| per_page | int  | 12      |
| page     | int  | 1       |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "author": {
      "id": 1,
      "name": "Sarah Chen",
      "avatar": "https://...",
      "bio": "Graphic designer and print enthusiast",
      "post_count": 20
    },
    "posts": {
      "data": [
        {
          "id": 1,
          "title": "10 Creative T-Shirt Design Ideas for 2026",
          "slug": "10-creative-tshirt-design-ideas-2026",
          "excerpt": "Discover the latest trends...",
          "featured_image": "https://...",
          "read_time": 5,
          "published_at": "2026-05-20T10:00:00Z"
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 12,
        "total": 20
      }
    }
  }
}
```

---

### 10.6 Get Related Posts

```
GET /blog/{slug}/related
```

**Query Params:**

| Param | Type | Default |
|-------|------|---------|
| limit | int  | 4       |

**Response `200`:**

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "title": "How to Prepare Your Design for Print",
      "slug": "how-to-prepare-design-for-print",
      "excerpt": "A step-by-step guide to getting print-ready files...",
      "featured_image": "https://...",
      "read_time": 7,
      "published_at": "2026-04-10T09:00:00Z"
    }
  ]
}
```

---

### 10.7 Subscribe to Newsletter

```
POST /newsletter
```

**Body:**

| Field | Type   | Required |
|-------|--------|----------|
| email | string | ✅        |
| name  | string | —        |

**Response `201`:**

```json
{
  "success": true,
  "message": "Successfully subscribed to newsletter"
}
```

**Response `422` (already subscribed):**

```json
{
  "success": false,
  "message": "This email is already subscribed"
}
```

---

## 11. Affiliate Program

> **Base URL:** `http://localhost:8000/api`
>
> The affiliate program allows any registered user to earn commissions by referring new customers. It operates on a **pay-per-sale** model: when a referred customer completes a purchase, the affiliate earns a percentage commission (default **15%**, configurable by admin).

### How It Works

1. Every registered user gets a **unique referral code** and **referral link**.
2. Affiliates share their referral link (`https://printpandora.com/r/{code}`).
3. When a visitor clicks the link, a **cookie is set** (30-day attribution window).
4. If the referred visitor registers and makes a purchase within the attribution window, the affiliate earns a **15% commission** on the order subtotal.
5. Commissions are credited when the order is **marked as completed/delivered**.
6. Affiliates can **withdraw** their available balance once they reach the minimum payout threshold.

### Key Terms

| Term | Description |
|------|-------------|
| **Referral Code** | Unique alphanumeric code assigned to each affiliate (auto-generated). |
| **Referral Link** | Public URL `https://printpandora.com/r/{code}` that sets a tracking cookie. |
| **Attribution Window** | 30 days from the first click. If the referred user buys within this window, the affiliate gets credit. |
| **Commission Rate** | Percentage of the order subtotal earned. Default 15%, configurable per affiliate. |
| **Conversion** | A referred visitor who registers and places an order. Counted only once per referred customer. |
| **Available Balance** | Commissions from completed/delivered orders that can be withdrawn. |
| **Pending Balance** | Commissions from orders not yet completed (processing/shipped). |
| **Minimum Payout** | Minimum amount required to request a withdrawal (default $25.00). |

---

### 11.1 Affiliate Dashboard

```
GET /affiliate
```

Returns the authenticated user's affiliate overview: referral info, traffic stats, and earnings summary.

**Headers:** `Authorization: Bearer {token}`

> 📌 **Note:** Every user automatically has an affiliate account. Calling this endpoint for the first time activates it and generates the referral code if one does not exist. No separate registration is required.

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "affiliate": {
      "id": 1,
      "referral_code": "JOHN42",
      "referral_link": "https://printpandora.com/r/JOHN42",
      "commission_rate": 15.00,
      "is_active": true,
      "activated_at": "2026-03-15T10:00:00Z"
    },
    "stats": {
      "total_clicks": 342,
      "total_referred_customers": 18,
      "total_conversions": 27,
      "conversion_rate": 7.89,
      "total_earned": 365.50,
      "available_balance": 210.25,
      "pending_balance": 155.25,
      "total_withdrawn": 120.00
    },
    "quick_links": {
      "referral_url": "https://printpandora.com/r/JOHN42",
      "share_text": "Check out PrintPandora for custom prints! Use my link for 10% off your first order.",
      "banners": [
        {
          "size": "728x90",
          "url": "https://cdn.printpandora.com/affiliate/banners/leaderboard-728x90.png"
        },
        {
          "size": "300x250",
          "url": "https://cdn.printpandora.com/affiliate/banners/rectangle-300x250.png"
        }
      ]
    }
  }
}
```

---

### 11.2 Traffic / Click Log

```
GET /affiliate/clicks
```

Paginated log of all referral link clicks, useful for tracking traffic sources and performance.

**Headers:** `Authorization: Bearer {token}`

**Query Params:**

| Param     | Type   | Default | Description                                    |
|-----------|--------|---------|------------------------------------------------|
| from      | string | —       | Filter clicks from date (ISO 8601, e.g. `2026-04-01T00:00:00Z`) |
| to        | string | —       | Filter clicks to date |
| source    | string | —       | Filter by traffic source: `direct`, `social`, `email`, `blog`, `other` |
| converted | boolean| —       | `true` = only clicks that led to conversion, `false` = only non-converting |
| per_page  | int    | 15      | Items per page (max 100) |
| page      | int    | 1       | Page number |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1023,
        "ip_hash": "a1b2c3...",
        "user_agent": "Mozilla/5.0 (Windows NT 10.0...)",
        "referrer": "https://twitter.com/johndoe/status/123",
        "source": "social",
        "country": "US",
        "device": "desktop",
        "converted": true,
        "converted_at": "2026-06-02T14:30:00Z",
        "clicked_at": "2026-06-01T10:15:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 23,
      "per_page": 15,
      "total": 342
    }
  }
}
```

---

### 11.3 Conversions

```
GET /affiliate/conversions
```

Paginated list of referred customer purchases (conversions). Each entry represents a completed order by a referred customer.

**Headers:** `Authorization: Bearer {token}`

**Query Params:**

| Param     | Type   | Default | Description                                    |
|-----------|--------|---------|------------------------------------------------|
| from      | string | —       | Filter conversions from date (ISO 8601) |
| to        | string | —       | Filter conversions to date |
| status    | string | —       | Filter by commission status: `pending`, `cleared`, `reversed` |
| per_page  | int    | 15      | Items per page (max 100) |
| page      | int    | 1       | Page number |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 56,
        "order": {
          "id": 204,
          "order_number": "PP-20260602-0204",
          "status": "delivered"
        },
        "customer": {
          "name": "Alice J.",
          "country": "US",
          "registered_at": "2026-06-01T10:30:00Z"
        },
        "order_subtotal": 89.97,
        "commission_rate": 15.00,
        "commission_amount": 13.50,
        "status": "cleared",
        "cleared_at": "2026-06-05T09:00:00Z",
        "created_at": "2026-06-02T14:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 15,
      "total": 27
    }
  }
}
```

---

### 11.4 Earnings Breakdown

```
GET /affiliate/earnings
```

Detailed earnings summary with breakdown by period and per-order detail.

**Headers:** `Authorization: Bearer {token}`

**Query Params:**

| Param    | Type   | Default | Description                                    |
|----------|--------|---------|------------------------------------------------|
| period   | string | all     | Group by: `today`, `week`, `month`, `year`, `all`, `custom` |
| from     | string | —       | Custom range start (ISO 8601, required when `period=custom`) |
| to       | string | —       | Custom range end (ISO 8601, required when `period=custom`) |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "period": {
      "label": "June 2026",
      "from": "2026-06-01T00:00:00Z",
      "to": "2026-06-30T23:59:59Z"
    },
    "summary": {
      "orders_referred": 5,
      "total_order_value": 349.85,
      "total_commission": 52.48,
      "average_commission_per_order": 10.50,
      "cleared": 38.98,
      "pending": 13.50,
      "reversed": 0.00
    },
    "daily_breakdown": [
      {
        "date": "2026-06-01",
        "conversions": 2,
        "commission": 19.49
      },
      {
        "date": "2026-06-02",
        "conversions": 1,
        "commission": 13.50
      },
      {
        "date": "2026-06-04",
        "conversions": 2,
        "commission": 19.49
      }
    ],
    "top_products": [
      {
        "product_id": 12,
        "product_name": "Premium Hoodie",
        "slug": "premium-hoodie",
        "image": "https://...",
        "units_sold": 3,
        "commission_earned": 18.75
      }
    ]
  }
}
```

---

### 11.5 Withdraw Earnings

```
POST /affiliate/withdraw
```

Request a payout of available earnings. The available balance must meet the minimum payout threshold.

**Headers:** `Authorization: Bearer {token}`

**Body:**

| Field           | Type   | Required | Description                                          |
|-----------------|--------|----------|------------------------------------------------------|
| amount          | number | ✅        | Amount to withdraw (must be ≤ available balance)     |
| payment_method  | string | ✅        | `paypal`, `bank_transfer`, `store_credit`            |
| payment_email   | string | *        | Required if `payment_method=paypal` — PayPal email   |
| bank_details    | object | *        | Required if `payment_method=bank_transfer`           |

**`bank_details` object:**

| Field           | Type   | Required | Description           |
|-----------------|--------|----------|-----------------------|
| account_name    | string | ✅        | Account holder name    |
| account_number  | string | ✅        | Bank account number    |
| routing_number  | string | ✅        | Routing / sort code    |
| bank_name       | string | ✅        | Bank name              |
| bank_country    | string | ✅        | Country code (e.g. "US") |

**Example (PayPal):**

```json
{
  "amount": 100.00,
  "payment_method": "paypal",
  "payment_email": "john@example.com"
}
```

**Example (Bank Transfer):**

```json
{
  "amount": 250.00,
  "payment_method": "bank_transfer",
  "bank_details": {
    "account_name": "John Doe",
    "account_number": "1234567890",
    "routing_number": "021000021",
    "bank_name": "Chase Bank",
    "bank_country": "US"
  }
}
```

**Response `201`:**

```json
{
  "success": true,
  "message": "Withdrawal request submitted. You will receive your payment within 5-7 business days.",
  "data": {
    "withdrawal": {
      "id": 12,
      "reference": "WD-20260605-0012",
      "amount": 100.00,
      "fee": 0.00,
      "net_amount": 100.00,
      "payment_method": "paypal",
      "payment_email": "john@example.com",
      "status": "pending",
      "created_at": "2026-06-05T10:00:00Z"
    },
    "remaining_balance": 110.25
  }
}
```

**Response `422` (insufficient balance):**

```json
{
  "success": false,
  "message": "Insufficient available balance",
  "errors": {
    "amount": [
      "Requested amount ($500.00) exceeds available balance ($210.25)."
    ]
  }
}
```

**Response `422` (below minimum payout):**

```json
{
  "success": false,
  "message": "Amount does not meet the minimum payout threshold",
  "errors": {
    "amount": [
      "Minimum withdrawal amount is $25.00."
    ]
  }
}
```

---

### 11.6 Withdrawal History

```
GET /affiliate/withdrawals
```

Paginated list of all withdrawal requests.

**Headers:** `Authorization: Bearer {token}`

**Query Params:**

| Param    | Type   | Default | Description                                    |
|----------|--------|---------|------------------------------------------------|
| status   | string | —       | Filter: `pending`, `processing`, `completed`, `rejected` |
| per_page | int    | 15      | Items per page (max 100) |
| page     | int    | 1       | Page number |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 12,
        "reference": "WD-20260605-0012",
        "amount": 100.00,
        "fee": 0.00,
        "net_amount": 100.00,
        "payment_method": "paypal",
        "payment_email": "john@example.com",
        "status": "pending",
        "status_note": null,
        "processed_at": null,
        "created_at": "2026-06-05T10:00:00Z"
      },
      {
        "id": 8,
        "reference": "WD-20260501-0008",
        "amount": 120.00,
        "fee": 2.50,
        "net_amount": 117.50,
        "payment_method": "paypal",
        "payment_email": "john@example.com",
        "status": "completed",
        "status_note": null,
        "processed_at": "2026-05-03T14:00:00Z",
        "created_at": "2026-05-01T08:30:00Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 2
    }
  }
}
```

---

### 11.7 Update Payment Settings

```
PATCH /affiliate/payment-settings
```

Save or update default payment method and details for faster future withdrawals.

**Headers:** `Authorization: Bearer {token}`

**Body:**

| Field           | Type   | Required | Description                                          |
|-----------------|--------|----------|------------------------------------------------------|
| default_method  | string | ✅        | `paypal`, `bank_transfer`, `store_credit`            |
| payment_email   | string | *        | PayPal email (required if method is `paypal`)        |
| bank_details    | object | *        | Bank details (required if method is `bank_transfer`) |

**Response `200`:**

```json
{
  "success": true,
  "message": "Payment settings updated",
  "data": {
    "default_method": "paypal",
    "payment_email": "john@example.com",
    "bank_details": null
  }
}
```

---

### 11.8 Traffic Sources Summary

```
GET /affiliate/sources
```

Aggregated breakdown of traffic by source (social, email, blog, direct, etc.) for optimizing marketing channels.

**Headers:** `Authorization: Bearer {token}`

**Query Params:**

| Param  | Type   | Default | Description                                    |
|--------|--------|---------|------------------------------------------------|
| from   | string | —       | Start date (ISO 8601) |
| to     | string | —       | End date (ISO 8601) |

**Response `200`:**

```json
{
  "success": true,
  "data": {
    "total_clicks": 342,
    "sources": [
      {
        "source": "social",
        "clicks": 180,
        "percentage": 52.63,
        "conversions": 14,
        "conversion_rate": 7.78,
        "revenue": 245.50
      },
      {
        "source": "email",
        "clicks": 65,
        "percentage": 19.01,
        "conversions": 8,
        "conversion_rate": 12.31,
        "revenue": 120.00
      },
      {
        "source": "direct",
        "clicks": 52,
        "percentage": 15.20,
        "conversions": 3,
        "conversion_rate": 5.77,
        "revenue": 45.00
      },
      {
        "source": "blog",
        "clicks": 30,
        "percentage": 8.77,
        "conversions": 1,
        "conversion_rate": 3.33,
        "revenue": 15.50
      },
      {
        "source": "other",
        "clicks": 15,
        "percentage": 4.39,
        "conversions": 1,
        "conversion_rate": 6.67,
        "revenue": 9.50
      }
    ]
  }
}
```

---

### 11.9 Public Referral Redirect (Track Click)

```
GET /r/{code}
```

The public-facing referral link. When a visitor opens this URL, a tracking cookie is set (30-day expiry) and the visitor is redirected to the store homepage. This endpoint does **not** require authentication — it is what affiliates share publicly.

**How tracking works:**

1. Visitor clicks `https://printpandora.com/r/JOHN42`
2. The server records the click (IP hash, user-agent, referrer, source, device, country).
3. A cookie `pp_ref=JOHN42` is set with a 30-day `Max-Age`.
4. The visitor is redirected to the main store (`/`).

**Behavior:**

- If a visitor already has an existing `pp_ref` cookie, the **first-touch attribution** model is used — the original referrer keeps the credit. The click is still logged but marked as `converted=false`.
- If the visitor arrives without a cookie, **last-touch attribution** is applied — this affiliate gets credit for any future conversion within 30 days.

**Query Params (optional — for tracking granularity):**

| Param  | Type   | Description                                      |
|--------|--------|--------------------------------------------------|
| src    | string | Traffic source hint: `social`, `email`, `blog`, `direct`, `other` |

**Example URLs:**

```
https://printpandora.com/r/JOHN42?src=social
https://printpandora.com/r/JANE99?src=email
```

**Response `302` (Redirect):**

```
Location: https://printpandora.com/?ref=JOHN42
Set-Cookie: pp_ref=JOHN42; Path=/; Max-Age=2592000; SameSite=Lax
```

> 📌 The `?ref=CODE` query param on the landing page is a fallback for clients that block third-party cookies. The frontend can read it and call the server to register the click via JS.

---

### Bonus: Use Referral Code at Registration

When a new user registers, they can optionally provide a referral code they received. This ensures attribution even if cookies are cleared.

**Updated Register endpoint (`POST /register`):**

| Field        | Type    | Required | Description              |
|--------------|---------|----------|--------------------------|
| referral_code| string  | —        | Affiliate referral code  |

When provided:

- The new user is permanently linked to the referring affiliate.
- The same 30-day attribution window still applies for purchase conversions.
- If both a `pp_ref` cookie and a `referral_code` field are present at registration, the `referral_code` field takes priority (explicit opt-in beats passive cookie).

**Example:**

```json
{
  "first_name": "Alice",
  "last_name": "Johnson",
  "email": "alice@example.com",
  "password": "securepass123",
  "country": "US",
  "referral_code": "JOHN42"
}
```

---

### Commission Lifecycle

```
Order Placed  →  Commission: Pending
Order Delivered  →  Commission: Cleared (added to available balance)
Order Refunded/Cancelled  →  Commission: Reversed
```

| Status | Description |
|--------|-------------|
| `pending` | Order has been placed but not yet delivered. Commission not available for withdrawal. |
| `cleared` | Order delivered and commission moved to available balance. Ready for withdrawal. |
| `reversed` | Order was refunded or cancelled. Commission revoked. |

### Admin Configuration (Internal)

The following settings are managed by the admin panel and are **not** available via the public API:

| Setting | Default | Description |
|---------|---------|-------------|
| Default commission rate | 15% | Global default for all affiliates |
| Per-affiliate commission rate | Inherits default | Override rate for specific affiliates |
| Minimum payout threshold | $25.00 | Minimum amount to request withdrawal |
| Attribution window | 30 days | Cookie lifespan for referral tracking |
| Payment processing time | 5–7 business days | SLA for processing withdrawals |

---

## 12. Pagination & Conventions

### Pagination

All list endpoints return paginated results with a `meta` object:

```json
{
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

Navigate pages with `?page=2`.

### Authentication

Authenticated endpoints require the header:

```
Authorization: Bearer {token}
```

The token is obtained from the `/register` or `/login` endpoints.

### Slug Convention

Resources like products and categories use URL-friendly slugs instead of IDs:

- `GET /products/classic-white-tee`
- `GET /categories/t-shirts`

### Image URLs

All image fields return full absolute URLs. The frontend should use them as-is.

### Price Format

All prices are in **decimal format** (e.g. `29.99`) representing USD unless otherwise noted.

### Date Format

All dates follow **ISO 8601**: `2026-06-03T14:30:00Z`

---

## Quick Endpoint Reference

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST   | `/register` | ❌ | Register new user |
| POST   | `/login` | ❌ | Login |
| POST   | `/logout` | ✅ | Logout |
| GET    | `/user` | ✅ | Get current user |
| POST   | `/forgot-password` | ❌ | Request password reset |
| GET    | `/categories` | ❌ | List categories |
| GET    | `/categories/tree` | ❌ | Get full category tree |
| GET    | `/categories/{slug}` | ❌ | Get category |
| GET    | `/products` | ❌ | List / filter products |
| GET    | `/products/featured` | ❌ | Featured products |
| GET    | `/products/{slug}` | ❌ | Product details |
| GET    | `/products/{slug}/reviews` | ❌ | Product reviews |
| POST   | `/products/{slug}/reviews` | ✅ | Submit review |
| GET    | `/cart` | ✅ | Get cart |
| POST   | `/cart` | ✅ | Add to cart |
| PATCH  | `/cart/{item}` | ✅ | Update cart item |
| DELETE | `/cart/{item}` | ✅ | Remove cart item |
| DELETE | `/cart` | ✅ | Clear cart |
| POST   | `/orders` | ✅ | Create order |
| GET    | `/orders` | ✅ | List orders |
| GET    | `/orders/{order}` | ✅ | Order details |
| POST   | `/orders/{order}/cancel` | ✅ | Cancel order |
| GET    | `/orders/{order}/receipt` | ✅ | Download order receipt |
| POST   | `/corporate-quotes` | ❌ | Submit corporate order quote |
| GET    | `/user/dashboard` | ✅ | User dashboard (profile + recent orders) |
| PATCH  | `/user/profile` | ✅ | Update profile |
| PUT    | `/user/password` | ✅ | Change password |
| GET    | `/addresses` | ✅ | List addresses |
| POST   | `/addresses` | ✅ | Add address |
| PATCH  | `/addresses/{address}` | ✅ | Update address |
| DELETE | `/addresses/{address}` | ✅ | Delete address |
| GET    | `/wishlist` | ✅ | Get wishlist |
| POST   | `/wishlist` | ✅ | Add to wishlist |
| DELETE | `/wishlist/{product}` | ✅ | Remove from wishlist |
| POST   | `/coupons/apply` | ❌ | Apply coupon |
| GET    | `/blog` | ❌ | List blog posts |
| GET    | `/blog/{slug}` | ❌ | Get blog post |
| GET    | `/blog/categories` | ❌ | List blog categories |
| GET    | `/blog/tags` | ❌ | List blog tags |
| GET    | `/blog/authors/{authorId}` | ❌ | Posts by author |
| GET    | `/blog/{slug}/related` | ❌ | Related posts |
| POST   | `/newsletter` | ❌ | Subscribe to newsletter |
| GET    | `/affiliate` | ✅ | Affiliate dashboard |
| GET    | `/affiliate/clicks` | ✅ | Traffic / click log |
| GET    | `/affiliate/conversions` | ✅ | Conversion history |
| GET    | `/affiliate/earnings` | ✅ | Earnings breakdown |
| POST   | `/affiliate/withdraw` | ✅ | Request withdrawal |
| GET    | `/affiliate/withdrawals` | ✅ | Withdrawal history |
| PATCH  | `/affiliate/payment-settings` | ✅ | Save payment defaults |
| GET    | `/affiliate/sources` | ✅ | Traffic sources breakdown |
| GET    | `/r/{code}` | ❌ | Public referral redirect |
