--send message api

curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "type": "text",
  "text": {
    "preview_url": false
  }
}'


--send image
curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "type": "image",
  "image": {
    "caption": ""
  }
}'

--send document
curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "type": "document",
  "document": {
    "caption": "",
    "filename": "document.pdf"
  }
}'

--send location
curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "type": "location",
  "location": {
    "name": "",
    "address": ""
  }
}'

--send template message
curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "type": "template",
  "template": {
    "language": {}
  }
}'

--Mark as Read & Show Typing
curl -X POST "https://graph.facebook.com/v24.0/1083367458184137/messages" \
  -H "Authorization: Bearer EAAG***SoDG" \
  -H "Content-Type: application/json" \
  -d '{
  "messaging_product": "whatsapp",
  "status": "read"
}'