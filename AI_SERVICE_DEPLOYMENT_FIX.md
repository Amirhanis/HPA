# AI Service Deployment Fix - Summary

## Issues Fixed

### 1. **503 Service Unavailable Error**
- **Cause**: AI service wasn't running or couldn't connect to database
- **Fix**: 
  - Added PostgreSQL support (Render uses PostgreSQL, not MySQL)
  - Updated database.py to auto-detect DB type and use appropriate connector
  - Added psycopg2-binary to requirements.txt

### 2. **Database Connection Issues**
- **Cause**: AI service .env file wasn't being created with proper credentials
- **Fix**: Updated entrypoint.sh to generate .env file from Laravel environment variables

### 3. **Missing Environment Variables**
- **Cause**: DB_CONNECTION and DB_PORT weren't being passed to AI service
- **Fix**: Added all necessary DB variables to entrypoint.sh

## Files Modified

1. **ai_service/database.py**
   - Added PostgreSQL support
   - Auto-detects DB type from DB_CONNECTION env var
   - Uses psycopg2 for PostgreSQL, mysql-connector for MySQL

2. **ai_service/requirements.txt**
   - Added: psycopg2-binary

3. **.render/entrypoint.sh**
   - Generates ai_service/.env with all DB credentials
   - Includes: DB_CONNECTION, DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, OPENROUTER_API_KEY

4. **.dockerignore**
   - Added Python cache files to reduce build size

## Render Environment Variables (Already Set)

✅ AI_SERVICE_URL=http://127.0.0.1:8001
✅ OPENROUTER_API_KEY=sk-or-v1-48ea9873...
✅ DB_CONNECTION=pgsql
✅ DB_HOST=dpg-d5eb9j2li9vc73dd7jr0-a
✅ DB_PORT=5432
✅ DB_DATABASE=hpa
✅ DB_USERNAME=hpa_user
✅ DB_PASSWORD=dxChSLq3zjon7cJtpcqnmT445zwXQFhX

## Deployment Steps

1. **Commit and Push Changes**
   ```bash
   git add .
   git commit -m "Fix AI service for Render deployment with PostgreSQL support"
   git push
   ```

2. **Render Will Auto-Deploy**
   - Watch the build logs in Render dashboard
   - Look for: "Creating AI service configuration..."
   - Verify all 3 services start: php-fpm, nginx, ai-service

3. **Verify AI Service is Running**
   Once deployed, check Render Shell:
   ```bash
   curl http://127.0.0.1:8001/
   ```
   Should return: `{"status": "AI Service Running"}`

4. **Test Recommendations**
   Visit: https://hpa-cz0p.onrender.com
   - AI recommendations should appear on homepage
   - Chatbot should respond with product suggestions

## How It Works Now

1. **Container Starts** → entrypoint.sh runs
2. **Creates ai_service/.env** with PostgreSQL credentials from Render env vars
3. **Supervisor starts 3 processes**:
   - php-fpm (Laravel)
   - nginx (Web server)
   - ai-service (FastAPI on port 8001)
4. **AI Service connects to PostgreSQL** using psycopg2
5. **Laravel calls AI service** at http://127.0.0.1:8001/recommendations

## Troubleshooting

If still getting 503:
1. Check Render logs for "ai-service" errors
2. Verify OPENROUTER_API_KEY is set in Render env vars
3. Check if PostgreSQL connection is successful
4. Ensure semantic model downloaded during build (check build logs)

## Local Development

For local testing with MySQL:
```bash
# ai_service/.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=hpa
OPENROUTER_API_KEY=your-key-here
```

Then run:
```bash
cd ai_service
python app.py
```
