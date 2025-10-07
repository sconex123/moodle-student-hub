# Student Mapper Moodle Plugin

A local plugin for Moodle to sync student data to an external application via REST API.

## Installation

1. Zip the `local_studentmapper` folder.
2. Go to **Site administration** > **Plugins** > **Install plugins**.
3. Upload the zip file or install from the Moodle plugins directory.
4. Follow the on-screen instructions to upgrade the database.

## Configuration

1. Go to **Site administration** > **Plugins** > **Local plugins** > **Student Mapper**.
2. **API URL**: Enter the POST endpoint of your external application (e.g., `https://your-app.com/api/webhooks/moodle/user`).
3. **API Token**: Enter the bearer token used to authenticate your request.
4. **Field Mappings**: Map Moodle fields to your external app's JSON keys.
   Format:
   ```text
   firstname:first_name
   lastname:last_name
   email:email
   idnumber:student_uid
   profile_field_custom1:external_custom_field
   ```

## connection

The plugin listens for:
- User Created
- User Updated

It sends a JSON payload to your configured URL:
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "moodle_id": 123
}
```
