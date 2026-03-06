# 📡 Social Media API Documentation

Complete reference for all social media platform API endpoints, authentication methods, and authentication credentials used in the SM Automation App.

---

## 📋 Table of Contents

1. [Facebook](#facebook)
2. [Instagram](#instagram)
3. [X (Twitter)](#x-twitter)
4. [LinkedIn](#linkedin)
5. [YouTube](#youtube)
6. [TikTok](#tiktok)
7. [Authentication Flow](#authentication-flow)
8. [Environment Configuration](#environment-configuration)
9. [Token Management](#token-management)
10. [Error Handling](#error-handling)

---

## Facebook

### 📊 Platform Overview
- **API Version**: v22.0 (Latest)
- **Base URL**: `https://graph.facebook.com/v22.0`
- **Auth Type**: OAuth 2.0
- **Rate Limits**: 600 calls per 600 seconds (per token)

### 🔐 Required Credentials
```
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URI=https://yourdomain.com/oauth/callback/facebook
```

### 🔑 OAuth Scopes Required
```
public_profile
pages_show_list
pages_manage_metadata
pages_read_engagement
pages_manage_posts
```

### 📍 API Endpoints

#### Text Post Publishing
```
POST /v22.0/{PAGE_ID}/feed
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "message": "Your post content here",
  "privacy": {
    "value": "PUBLIC"  // PUBLIC, FRIENDS, CUSTOM, SELF
  }
}

Response: { "id": "page_id_post_id" }
```

#### Photo Publishing
```
POST /v22.0/{PAGE_ID}/photos
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "url": "https://example.com/image.jpg",
  "caption": "Photo caption here",
  "privacy": {
    "value": "PUBLIC"
  }
}

Response: { "id": "photo_id", "post_id": "post_id" }
```

#### Video Publishing
```
POST /v22.0/{PAGE_ID}/videos
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "file_url": "https://example.com/video.mp4",
  "description": "Video description",
  "published": true,
  "privacy": {
    "value": "PUBLIC"
  }
}

Response: { "id": "video_id" }
```

#### Post Deletion
```
DELETE /v22.0/{POST_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: { "success": true }
```

#### Get Page Access Token
```
GET /v22.0/me/accounts?fields=access_token,name,id
Authorization: Bearer {USER_ACCESS_TOKEN}

Response: {
  "data": [
    {
      "access_token": "page_access_token",
      "name": "Page Name",
      "id": "page_id"
    }
  ]
}
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **Profile Data**: Contains page info in `profile_data` JSON
- **Expiration**: Long-lived tokens (expires in ~2 months)

### 🐛 Common Errors
- `OAuthException 100`: Invalid parameter
- `OAuthException 190`: Invalid/expired access token
- `OAuthException 200`: Permission denied
- `OAuthException 355`: Video format not supported

---

## Instagram

### 📊 Platform Overview
- **API Version**: v22.0 (Latest)
- **Base URL**: `https://graph.instagram.com/v22.0`
- **Auth Type**: OAuth 2.0 (via Facebook)
- **Rate Limits**: Same as Facebook (shared token)
- **Media Types**: IMAGE, VIDEO, REELS

### 🔐 Required Credentials
```
INSTAGRAM_CLIENT_ID=your_app_id
INSTAGRAM_CLIENT_SECRET=your_app_secret
INSTAGRAM_REDIRECT_URI=https://yourdomain.com/oauth/callback/instagram
```

### 🔑 OAuth Scopes Required
```
instagram_basic
instagram_content_publish
instagram_manage_insights
pages_show_list
pages_read_engagement
business_management
```

### 📍 API Endpoints

#### Image Publishing (2-Step Process)

**Step 1: Create Media Container**
```
POST /v22.0/{INSTAGRAM_USER_ID}/media
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "image_url": "https://example.com/image.jpg",
  "caption": "Your caption",
  "media_type": "IMAGE",
  "alt_text": "Alt text for accessibility"
}

Response: { "id": "creation_id" }
```

**Step 2: Publish Media**
```
POST /v22.0/{INSTAGRAM_USER_ID}/media_publish
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "creation_id": "creation_id_from_step_1"
}

Response: { "id": "published_media_id" }
```

#### Video/Reels Publishing (2-Step Process)

**Step 1: Create Video Container**
```
POST /v22.0/{INSTAGRAM_USER_ID}/media
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "video_url": "https://example.com/video.mp4",
  "caption": "Your caption",
  "media_type": "REELS",  // VIDEO (deprecated), use REELS
  "thumb_offset": 0
}

Response: { "id": "creation_id" }
```

**Step 2: Publish Media**
```
POST /v22.0/{INSTAGRAM_USER_ID}/media_publish
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "creation_id": "creation_id_from_step_1"
}

Response: { "id": "published_media_id" }
```

#### Enable Comments
```
POST /v22.0/{MEDIA_ID}/comments_enabled
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "enable": true
}
```

#### Get User Info
```
GET /v22.0/{INSTAGRAM_USER_ID}?fields=id,username,name,biography,profile_picture_url
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "id": "instagram_user_id",
  "username": "username",
  "name": "Full Name",
  "biography": "Bio",
  "profile_picture_url": "https://..."
}
```

#### Media Deletion
```
DELETE /v22.0/{MEDIA_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: { "success": true }
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **Instagram User ID**: In `social_accounts.account_id` or `profile_data`
- **Profile Data**: Contains user info and media stats
- **Expiration**: Long-lived tokens (expires in ~2 months)

### ⚠️ Important Notes
- Two-step publishing process (create container, then publish)
- Video processing can take time (polling required)
- Maximum 5 requests per minute per IGID
- Minimum video length: 3 seconds
- Maximum video length: 60 seconds for Reels, 3600 seconds for IGTV

### 🐛 Common Errors
- `OAuthException 100`: Invalid media_type
- `OAuthException 190`: Invalid/expired access token
- `Exception 368`: Page access token cannot be used for this operation
- `Exception 9005`: Media is still being encoded

---

## X (Twitter)

### 📊 Platform Overview
- **API Version**: v2 (Latest)
- **Base URL**: `https://api.x.com/2`
- **Auth Type**: OAuth 2.0 with PKCE
- **Rate Limits**: 300 requests per 15 minutes (user context)

### 🔐 Required Credentials
```
X_CLIENT_ID=your_client_id
X_CLIENT_SECRET=your_client_secret
X_REDIRECT_URI=https://yourdomain.com/oauth/callback/x
X_API_KEY=your_api_key
X_API_SECRET=your_api_secret
```

### 🔑 OAuth Scopes Required
```
tweet.read
tweet.write
users.read
offline.access
```

### 📍 API Endpoints

#### Tweet (Text) Publishing
```
POST /2/tweets
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "text": "Your tweet content here"
}

Response: {
  "data": {
    "id": "tweet_id",
    "text": "Your tweet content here"
  }
}
```

#### Tweet with Media
```
POST /2/tweets
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "text": "Tweet with media",
  "media": {
    "media_ids": ["media_id_1", "media_id_2"]
  }
}

Response: {
  "data": {
    "id": "tweet_id"
  }
}
```

#### Upload Media

**Step 1: Initialize Upload**
```
POST /1.1/media/upload.json
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/x-www-form-urlencoded

{
  "command": "INIT",
  "total_bytes": 1024000,
  "media_type": "video/mp4"
}

Response: {
  "media_id": "media_id",
  "expires_after_secs": 3600
}
```

**Step 2: Append Media Data**
```
POST /1.1/media/upload.json
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/x-www-form-urlencoded

{
  "command": "APPEND",
  "media_id": "media_id",
  "media_data": "base64_encoded_chunk"
}
```

**Step 3: Finalize Upload**
```
POST /1.1/media/upload.json
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/x-www-form-urlencoded

{
  "command": "FINALIZE",
  "media_id": "media_id"
}

Response: {
  "media_id": "media_id",
  "size": 1024000,
  "expires_after_secs": 3600,
  "image": {
    "image_type": "image/jpeg",
    "w": 1200,
    "h": 675
  }
}
```

#### Tweet Deletion
```
DELETE /2/tweets/{TWEET_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "data": {
    "deleted": true
  }
}
```

#### Get Tweet Details
```
GET /2/tweets/{TWEET_ID}?tweet.fields=created_at,public_metrics
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "data": {
    "id": "tweet_id",
    "text": "Tweet content",
    "created_at": "2026-01-15T10:30:00.000Z",
    "public_metrics": {
      "retweet_count": 10,
      "reply_count": 5,
      "like_count": 45,
      "quote_count": 2
    }
  }
}
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **Tweet ID**: In `platform_specific_data['x']['data']['id']`
- **User ID**: In `social_accounts.account_id`
- **Expiration**: 2-hour expiration, requires refresh token

### 🔄 Token Refresh
```
POST https://api.x.com/2/oauth2/token
Content-Type: application/x-www-form-urlencoded

{
  "grant_type": "refresh_token",
  "refresh_token": "{REFRESH_TOKEN}",
  "client_id": "{CLIENT_ID}",
  "client_secret": "{CLIENT_SECRET}"
}

Response: {
  "token_type": "Bearer",
  "expires_in": 7200,
  "access_token": "new_access_token",
  "refresh_token": "new_refresh_token"
}
```

### ⚠️ Important Notes
- Uses PKCE (Proof Key for Public Clients) flow
- Short-lived tokens (2 hours) - refresh required
- Media upload has 5MB size limit for images, 15MB for videos
- Must use v2 API for posting (v1.1 for media upload)
- Different authentication needed for different endpoints

### 🐛 Common Errors
- `401 Unauthorized`: Invalid or expired access token
- `403 Forbidden`: Permission denied
- `404 Not Found`: Tweet or media not found
- `429 Too Many Requests`: Rate limit exceeded

---

## LinkedIn

### 📊 Platform Overview
- **API Version**: v2 (Latest)
- **Base URL**: `https://api.linkedin.com/rest`
- **Auth Type**: OAuth 2.0
- **Rate Limits**: 100 requests per day (organizational posts), 20 per day (user posts)

### 🔐 Required Credentials
```
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret
LINKEDIN_REDIRECT_URI=https://yourdomain.com/oauth/callback/linkedin
```

### 🔑 OAuth Scopes Required
```
openid
profile
email
w_member_social
```

### 📍 API Endpoints

#### Text Post Publishing
```
POST /rest/posts
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json
LinkedIn-Version: 202602

{
  "author": "urn:li:person:{PERSON_ID}",
  "commentary": "Your post content here #hashtag",
  "visibility": "PUBLIC",
  "lifecycleState": "PUBLISHED",
  "isReshareDisabledByAuthor": false
}

Response Headers:
  x-restli-id: "post_urn_id"

Response: {
  "created": 1234567890,
  "...": "..."
}
```

#### Post with Image

**Step 1: Register Asset Upload**
```
POST /assets?action=registerUpload
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json

{
  "registerUploadRequest": {
    "recipes": ["urn:li:digitalmediaRecipe:feedshare-image"],
    "owner": "urn:li:person:{PERSON_ID}",
    "serviceRelationships": [
      {
        "relationshipType": "OWNER",
        "identifier": "urn:li:userGeneratedContent"
      }
    ]
  }
}

Response: {
  "value": {
    "mediaUploadHttpRequest": {
      "uploadUrl": "https://upload.linkedin.com/...",
      "httpMethod": "PUT"
    },
    "mediaAsset": "urn:li:digitalmediaAsset:..."
  }
}
```

**Step 2: Upload Image**
```
PUT {uploadUrl}
Content-Type: image/jpeg

[binary image data]

Response: 204 No Content
```

**Step 3: Create Post with Image**
```
POST /posts
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json
LinkedIn-Version: 202602

{
  "author": "urn:li:person:{PERSON_ID}",
  "commentary": "Post with image",
  "content": {
    "media": {
      "id": "urn:li:digitalmediaAsset:...",
      "title": "Image Title"
    }
  },
  "visibility": "PUBLIC",
  "lifecycleState": "PUBLISHED"
}
```

#### Post with Video

**Step 1: Register Video Upload**
```
POST /videos?action=initializeUpload
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json

{
  "initializeUploadRequest": {
    "owner": "urn:li:person:{PERSON_ID}",
    "fileSizeBytes": 1024000,
    "uploadCaptions": false
  }
}

Response: {
  "value": {
    "video": "urn:li:video:{VIDEO_ID}",
    "uploadToken": "upload_token",
    "uploadUrl": "https://upload.linkedin.com/..."
  }
}
```

**Step 2: Upload Video File**
```
POST {uploadUrl}
Content-Type: video/mp4

[binary video data]
```

**Step 3: Finalize Upload**
```
POST /videos?action=finalizeUpload
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json

{
  "finalizeUploadRequest": {
    "video": "urn:li:video:{VIDEO_ID}",
    "uploadToken": "upload_token"
  }
}
```

**Step 4: Create Post with Video**
```
POST /posts
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0
Content-Type: application/json
LinkedIn-Version: 202602

{
  "author": "urn:li:person:{PERSON_ID}",
  "commentary": "Check out this video!",
  "content": {
    "media": {
      "id": "urn:li:video:{VIDEO_ID}",
      "title": "Video Title"
    }
  },
  "visibility": "PUBLIC",
  "lifecycleState": "PUBLISHED"
}
```

#### Post Deletion
```
DELETE /posts/{POST_ID}
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0

Response: 204 No Content
```

#### Get User Info
```
GET /me?fields=id,localizedFirstName,localizedLastName,profilePicture(displayImage)
Authorization: Bearer {ACCESS_TOKEN}
X-Restli-Protocol-Version: 2.0.0

Response: {
  "localizedFirstName": "First",
  "localizedLastName": "Last",
  "id": "person_id",
  "profilePicture": {
    "displayImage": "urn:li:digitalmediaAsset:..."
  }
}
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **Person ID**: In `social_accounts.account_id`
- **Post ID**: In `platform_specific_data['post_id']` (from x-restli-id header)
- **Expiration**: 60-day lifetime

### ⚠️ Important Notes
- Uses x-restli protocol (special headers required)
- Post ID returned in response header (x-restli-id), not in body
- Video not immediately available after upload (processing time)
- Different rate limits for user posts vs organizational posts
- Maximum 10 posts per day per user

### 🐛 Common Errors
- `401 Unauthorized`: Invalid access token
- `403 Forbidden`: Scope or permission issues
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Video still processing

---

## YouTube

### 📊 Platform Overview
- **API Version**: v3 (Latest)
- **Base URL**: `https://www.googleapis.com/youtube/v3`
- **Upload API**: `https://www.googleapis.com/upload/youtube/v3`
- **Auth Type**: OAuth 2.0
- **Rate Limits**: 10,000 quota units per day (default)

### 🔐 Required Credentials
```
YOUTUBE_CLIENT_ID=your_client_id.apps.googleusercontent.com
YOUTUBE_CLIENT_SECRET=your_client_secret
YOUTUBE_REDIRECT_URI=https://yourdomain.com/oauth/callback/youtube
```

### 🔑 OAuth Scopes Required
```
https://www.googleapis.com/auth/youtube.upload
https://www.googleapis.com/auth/youtube
https://www.googleapis.com/auth/youtube.readonly
https://www.googleapis.com/auth/userinfo.profile
https://www.googleapis.com/auth/userinfo.email
```

### 📍 API Endpoints

#### Upload Video (Resumable Upload)

**Step 1: Initiate Resumable Upload**
```
POST /upload/youtube/v3/videos?uploadType=resumable&part=snippet,status,processingDetails
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json
X-Upload-Content-Type: video/mp4
X-Upload-Content-Length: {VIDEO_FILE_SIZE}

{
  "snippet": {
    "title": "Video Title",
    "description": "Video Description",
    "tags": ["tag1", "tag2"],
    "categoryId": "22",
    "defaultLanguage": "en",
    "localized": {
      "title": "Video Title",
      "description": "Video Description"
    }
  },
  "status": {
    "privacyStatus": "public",      // public, private, unlisted
    "selfDeclaredMadeForKids": false,
    "license": "creativeCommon"     // youtube, creativeCommon
  },
  "processingDetails": {
    "processingStatus": "processing"
  }
}

Response Headers:
  Location: https://www.googleapis.com/upload/youtube/v3/videos?...&session_uri={SESSION_URI}
  
Response: 200 OK
```

**Step 2: Upload Video Content**
```
PUT {Location_Header_URI}
Content-Type: video/mp4
Content-Length: {VIDEO_FILE_SIZE}

[binary video file data]

Response: {
  "id": "youtube_video_id",
  "snippet": {
    "title": "Video Title",
    "description": "Video Description",
    "tags": ["tag1", "tag2"],
    "categoryId": "22"
  },
  "status": {
    "uploadStatus": "uploaded",
    "privacyStatus": "public",
    "processingStatus": "processing"
  }
}
```

#### Get Video Status
```
GET /videos?part=status,processingDetails&id={VIDEO_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "items": [
    {
      "id": "video_id",
      "status": {
        "uploadStatus": "processed",
        "privacyStatus": "public",
        "processingStatus": "succeeded"
      },
      "processingDetails": {
        "uploadAvailability": "available"
      }
    }
  ]
}
```

#### Update Video Metadata
```
PUT /videos?part=snippet,status
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "id": "video_id",
  "snippet": {
    "title": "Updated Title",
    "description": "Updated Description",
    "tags": ["updated", "tags"],
    "categoryId": "22"
  },
  "status": {
    "privacyStatus": "public"
  }
}
```

#### Delete Video
```
DELETE /videos?id={VIDEO_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: 204 No Content
```

#### Get Channel Info
```
GET /channels?part=snippet,contentDetails,statistics&mine=true
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "items": [
    {
      "id": "channel_id",
      "snippet": {
        "title": "Channel Title",
        "description": "Channel Description",
        "thumbnails": {
          "default": { "url": "https://..." }
        }
      },
      "statistics": {
        "viewCount": "1000000",
        "commentCount": "500",
        "subscriberCount": "10000",
        "videoCount": "50"
      }
    }
  ]
}
```

#### Get Authenticated User
```
GET /channels?part=snippet&mine=true&fields=items(id,snippet(title,description))
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "items": [
    {
      "id": "channel_id",
      "snippet": {
        "title": "Channel Title",
        "description": "Description"
      }
    }
  ]
}
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **Channel ID**: In `social_accounts.account_id`
- **Video ID**: In `platform_specific_data['youtube_video_id']`
- **Expiration**: Long-lived tokens (expires in ~3.7 years or requires refresh)

### 🔄 Token Refresh
```
POST https://oauth2.googleapis.com/token
Content-Type: application/x-www-form-urlencoded

{
  "grant_type": "refresh_token",
  "refresh_token": "{REFRESH_TOKEN}",
  "client_id": "{CLIENT_ID}",
  "client_secret": "{CLIENT_SECRET}"
}

Response: {
  "access_token": "new_access_token",
  "expires_in": 3599,
  "refresh_token": "refresh_token",
  "scope": "https://www.googleapis.com/auth/youtube.upload ...",
  "token_type": "Bearer"
}
```

### ⚠️ Important Notes
- Uses resumable upload (handles large files and network interruptions)
- Video processing takes time (can be minutes to hours)
- Must wait for upload completion before accessing video
- Supported formats: MP4, MOV, AVI, WMV, FLV, 3GP, WEBM, MKV
- Maximum file size: 256 GB
- Minimum duration: 0 seconds, Standard: up to 12 hours

### 🐛 Common Errors
- `401 Unauthorized`: Invalid access token
- `403 Forbidden`: Quota exceeded or permission denied
- `400 Bad Request`: Invalid video parameters
- `503 Service Unavailable`: Backend service unavailable

---

## TikTok

### 📊 Platform Overview
- **API Version**: v2 (Latest)
- **Base URL**: `https://open.tiktokapis.com/v2`
- **Auth Type**: OAuth 2.0
- **Rate Limits**: Varies by operation (typically 200-500 requests/day)

### 🔐 Required Credentials
```
TIKTOK_CLIENT_ID=your_client_key
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URI=https://yourdomain.com/oauth/callback/tiktok
```

### 🔑 OAuth Scopes Required
```
user.info.basic
video.list
video.upload
video.publish
```

### 📍 API Endpoints

#### Video Upload (Initialize)
```
POST /post/publish/video/init/
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "post_info": {
    "title": "Video Title",
    "privacy_level": "PUBLIC_TO_EVERYONE",  // PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, PRIVATE
    "disable_duet": false,
    "disable_comment": false,
    "disable_stitch": false
  },
  "source_info": {
    "source": "PULL_FROM_URL",
    "video_url": "https://example.com/video.mp4"
  }
}

Response: {
  "data": {
    "publish_id": "publish_id_for_tracking",
    "upload_url": "https://upload.tiktokapis.com/..."  // optional
  }
}
```

#### Check Video Upload Status
```
GET /post/publish/status/fetch/?publish_id={PUBLISH_ID}
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "data": {
    "status": "PROCESSING_UPLOAD",  // PROCESSING_UPLOAD, PROCESSING_PUBLISH, PUBLISH_COMPLETE, PUBLISH_FAILED
    "error": null,
    "video_id": "video_id"  // Only after PUBLISH_COMPLETE
  }
}
```

#### Photo Post Upload (Initialize)
```
POST /post/publish/content/init/
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "post_info": {
    "title": "Photo Caption",
    "privacy_level": "PUBLIC_TO_EVERYONE",
    "disable_comment": false,
    "auto_add_music": false,
    "disable_duet": false,
    "disable_stitch": false
  },
  "source_info": {
    "source": "PULL_FROM_URL",
    "photo_images": [
      "https://example.com/photo1.jpg",
      "https://example.com/photo2.jpg"
    ]
  },
  "post_mode": "DIRECT_POST",
  "media_type": "PHOTO"
}

Response: {
  "data": {
    "publish_id": "publish_id_for_tracking"
  }
}
```

#### Text Post (No Media)
```
POST /post/publish/content/init/
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json

{
  "post_info": {
    "title": "Your text content here",
    "privacy_level": "PUBLIC_TO_EVERYONE"
  },
  "source_info": {
    "source": "PULL_FROM_URL"
  },
  "post_mode": "DIRECT_POST",
  "media_type": "TEXT"
}

Response: {
  "data": {
    "publish_id": "publish_id_for_tracking"
  }
}
```

#### Get User Info
```
GET /user/info/?fields=open_id,union_id,avatar_large,avatar_large_url,display_name
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "data": {
    "user": {
      "open_id": "user_open_id",
      "union_id": "user_union_id",
      "avatar_large_url": "https://...",
      "display_name": "Username"
    }
  }
}
```

#### List User Videos
```
GET /user/video/list/?fields=id,create_time,title,cover_image_url,share_url,view_count,like_count,comment_count,share_count
Authorization: Bearer {ACCESS_TOKEN}

Response: {
  "data": {
    "videos": [
      {
        "id": "video_id",
        "create_time": 1234567890,
        "title": "Video Title",
        "share_url": "https://www.tiktok.com/...",
        "view_count": 100,
        "like_count": 20,
        "comment_count": 5,
        "share_count": 2
      }
    ],
    "cursor": "next_page_cursor"
  }
}
```

### 💾 Data Storage
- **Access Token**: Stored in `social_accounts.access_token`
- **User Open ID**: In `social_accounts.account_id`
- **Publish ID**: In `platform_specific_data['publish_id']` (for tracking)
- **Video ID**: Available after PUBLISH_COMPLETE status
- **Expiration**: 24-hour lifetime, refresh required daily

### 🔄 Token Refresh
```
POST https://open.tiktokapis.com/v2/oauth/token/
Content-Type: application/x-www-form-urlencoded

{
  "client_key": "{CLIENT_KEY}",
  "client_secret": "{CLIENT_SECRET}",
  "grant_type": "refresh_token",
  "refresh_token": "{REFRESH_TOKEN}"
}

Response: {
  "data": {
    "access_token": "new_access_token",
    "refresh_token": "new_refresh_token",
    "expires_in": 86400,
    "scope": "user.info.basic video.list video.upload video.publish"
  }
}
```

### ⚠️ Important Notes
- Post publishing is **asynchronous** - must poll status
- Video processing can take 30 seconds to several minutes
- **Delete API not available** - posts cannot be deleted programmatically
- Tokens only valid for 24 hours - requires daily refresh
- Single video max size: 287.6 MB
- Supported video formats: H.264/H.265 codec, MP4, MOV, AVI, 3GP, WEBM, FLV, MKV, WMV
- Upload multiple photos in sequence for carousel posts
- Rate limits vary - monitor API responses for rate limit headers

### 🐛 Common Errors
- `error_code 10000`: Invalid parameters
- `error_code 40001`: Unauthorized (token expired/invalid)
- `error_code 40002`: Forbidden (no permission)
- `error_code 50000`: Internal server error
- Status: `PUBLISH_FAILED` - Check response error message

---

## Authentication Flow

### 🔐 Step-by-Step OAuth Process

#### 1. Redirect to Platform
Generate OAuth URL with state parameter for CSRF protection:
```php
$state = Str::random(40);
session(['oauth_state_' . $platform => $state]);

$url = "https://oauth.platform.com/authorize?" . http_build_query([
    'client_id' => env('PLATFORM_CLIENT_ID'),
    'redirect_uri' => route('oauth.callback', $platform),
    'scope' => 'specific.scopes',
    'response_type' => 'code',
    'state' => $state,
]);

return redirect($url);
```

#### 2. User Authorizes
User logs in and grants permissions to your app.

#### 3. Platform Redirects Back
```
GET https://yourdomain.com/oauth/callback/{platform}?code={AUTHORIZATION_CODE}&state={STATE}
```

#### 4. Exchange Code for Token
```php
$response = Http::post('https://oauth.platform.com/token', [
    'grant_type' => 'authorization_code',
    'code' => $request->code,
    'client_id' => env('PLATFORM_CLIENT_ID'),
    'client_secret' => env('PLATFORM_CLIENT_SECRET'),
    'redirect_uri' => route('oauth.callback', $platform),
]);

$token = $response->json();
// Store token in social_accounts.access_token
// Store expiration in social_accounts.token_expires_at
// Store refresh token if available
```

#### 5. Store Tokens Securely
```php
SocialAccount::create([
    'user_id' => Auth::id(),
    'platform' => $platform,
    'account_id' => $token['user_id'],
    'access_token' => $token['access_token'],
    'refresh_token' => $token['refresh_token'] ?? null,
    'token_expires_at' => now()->addSeconds($token['expires_in']),
    'profile_data' => $profileData, // JSON with user info
    'is_active' => true,
]);
```

### 🔄 Token Refresh Process

When token expires or returns 401:
```php
if ($response->status() === 401) {
    // Token expired, refresh it
    $newToken = $this->refreshToken($socialAccount);
    
    // Update in database
    $socialAccount->update([
        'access_token' => $newToken['access_token'],
        'refresh_token' => $newToken['refresh_token'] ?? $socialAccount->refresh_token,
        'token_expires_at' => now()->addSeconds($newToken['expires_in']),
    ]);
    
    // Retry original request
    return $this->makeApiRequest(...);
}
```

### 🛡️ Security Best Practices
- ✅ Always validate state parameter
- ✅ Store tokens encrypted in database
- ✅ Use HTTPS for all OAuth redirects
- ✅ Store refresh tokens securely
- ✅ Implement token expiration checking
- ✅ Never expose tokens in logs
- ✅ Use short-lived access tokens when possible
- ✅ Implement rate limiting on token endpoints

---

## Environment Configuration

### 📝 Required `.env` Variables

```bash
# Facebook
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URI=${APP_URL}/oauth/callback/facebook

# Instagram (uses Facebook OAuth)
INSTAGRAM_CLIENT_ID=your_app_id
INSTAGRAM_CLIENT_SECRET=your_app_secret
INSTAGRAM_REDIRECT_URI=${APP_URL}/oauth/callback/instagram

# X (Twitter)
X_CLIENT_ID=your_client_id
X_CLIENT_SECRET=your_client_secret
X_REDIRECT_URI=${APP_URL}/oauth/callback/x
X_API_KEY=your_api_key
X_API_SECRET=your_api_secret

# LinkedIn
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret
LINKEDIN_REDIRECT_URI=${APP_URL}/oauth/callback/linkedin

# YouTube
YOUTUBE_CLIENT_ID=your_client_id.apps.googleusercontent.com
YOUTUBE_CLIENT_SECRET=your_client_secret
YOUTUBE_REDIRECT_URI=${APP_URL}/oauth/callback/youtube

# TikTok
TIKTOK_CLIENT_ID=your_client_key
TIKTOK_CLIENT_SECRET=your_client_secret
TIKTOK_REDIRECT_URI=${APP_URL}/oauth/callback/tiktok

# General Settings
APP_URL=https://yourdomain.com
APP_DEBUG=false
QUEUE_CONNECTION=database
LOG_LEVEL=warning
```

### 🔐 Obtaining Credentials

**Facebook / Instagram:**
1. Go to https://developers.facebook.com/
2. Create an app
3. Enable Facebook Login and Instagram Graph API products
4. Get App ID and App Secret

**X (Twitter):**
1. Go to https://developer.x.com/
2. Create a project and app
3. Enable OAuth 2.0
4. Generate API Key and Secret
5. Add callback URLs to app settings

**LinkedIn:**
1. Go to https://www.linkedin.com/developers/apps
2. Create an app
3. Request access to Sign In with LinkedIn and/or Share on LinkedIn
4. Get Client ID and Client Secret

**YouTube:**
1. Go to https://console.cloud.google.com/
2. Create project
3. Enable YouTube Data API v3
4. Create OAuth 2.0 credentials (Desktop application)
5. Download credentials JSON

**TikTok:**
1. Go to https://developers.tiktok.com/
2. Create an app
3. Apply for developer access
4. Get Client Key and Client Secret
5. Set redirect URI in marketplace

---

## Token Management

### 🕐 Token Expiration Schedule

| Platform | Access Token | Refresh Token | Auto-Refresh |
|----------|-------------|---------------|--------------|
| Facebook | ~2 months | Long-lived | Manual |
| Instagram | ~2 months | Long-lived | Manual |
| X | 2 hours | 6 months | Required |
| LinkedIn | 60 days | Long-lived (1 year+) | Manual |
| YouTube | ~1 hour | Long-lived | Recommended |
| TikTok | 24 hours | 365 days | **Required** |

### 🔄 Automatic Token Refresh

Create a scheduled command to refresh expiring tokens:

```php
// app/Console/Commands/RefreshSocialTokens.php
<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use Illuminate\Console\Command;

class RefreshSocialTokens extends Command
{
    protected $signature = 'tokens:refresh';
    protected $description = 'Refresh expiring social media tokens';

    public function handle()
    {
        // Refresh tokens expiring within 24 hours
        $accounts = SocialAccount::whereDate('token_expires_at', '<=', now()->addDay())
            ->where('token_expires_at', '>', now())
            ->get();

        foreach ($accounts as $account) {
            $this->refreshToken($account);
        }

        $this->info('Tokens refreshed successfully.');
    }

    private function refreshToken(SocialAccount $account)
    {
        $oauthService = app('OAuthService');
        $oauthService->refreshToken($account);
    }
}
```

Schedule in `app/Console/Kernel.php`:
```php
$schedule->command('tokens:refresh')->hourly()->onOneServer();
```

### ⚠️ Expired Token Handling

When API returns 401 or token expired error:

```php
protected function handleExpiredToken(SocialAccount $account)
{
    if (!$account->refresh_token) {
        // Cannot refresh - need re-authorization
        $account->update(['is_active' => false]);
        Log::warning("Token cannot be refreshed for account {$account->id}");
        return false;
    }

    try {
        $newToken = $this->refreshTokenFromPlatform($account);
        $account->update([
            'access_token' => $newToken['access_token'],
            'refresh_token' => $newToken['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => now()->addSeconds($newToken['expires_in']),
        ]);
        return true;
    } catch (\Exception $e) {
        Log::error("Failed to refresh token: " . $e->getMessage());
        $account->update(['is_active' => false]);
        return false;
    }
}
```

---

## Error Handling

### 🚨 Common API Errors

#### 401 Unauthorized
```
Problem: Invalid or expired access token
Solution: Refresh token or require re-authorization
Response Headers: {"WWW-Authenticate": "Bearer error=\"invalid_token\""}
```

#### 403 Forbidden
```
Problem: Insufficient permissions or scope
Solution: Check OAuth scopes, request additional permissions
Response Body: {"error": {"type": "OAuthException", "message": "...scope not granted"}}
```

#### 429 Too Many Requests
```
Problem: Rate limit exceeded
Solution: Implement exponential backoff, queue jobs
Response Headers: {"Retry-After": 60}
```

#### 400 Bad Request
```
Problem: Invalid request parameters
Solution: Validate request before sending
Response Body: {"error": {"message": "Invalid parameter..."}}
```

#### 500 Internal Server Error
```
Problem: Platform API error
Solution: Retry with exponential backoff
Response Body: {"error": {"type": "ServerException"}}
```

### 🔍 Debugging API Issues

#### Enable API Logging
```php
// In .env
LOG_LEVEL=debug

// Logging wrapper
protected function makeApiRequest($method, $url, $payload = [], $headers = [])
{
    Log::debug('API Request', [
        'method' => $method,
        'url' => $url,
        'platform' => $this->getPlatformFromUrl($url),
    ]);

    $response = Http::withHeaders($headers)->{$method}($url, $payload);

    if (!$response->successful()) {
        Log::error('API Error', [
            'status' => $response->status(),
            'body' => $response->json(),
            'headers' => $response->headers(),
        ]);
    }

    return $response;
}
```

#### Check Response Headers
```
Authorization: Bearer {token}
X-Rate-Limit-Limit: 100
X-Rate-Limit-Remaining: 95
X-Rate-Limit-Reset: 1234567890
Retry-After: 60
```

#### Monitor Platform Status
- Facebook: https://developers.facebook.com/status
- X: https://api.x.com/status
- LinkedIn: https://engineering.linkedin.com/blog
- YouTube: https://www.google.com/appsstatus
- TikTok: https://developers.tiktok.com/news

---

## Testing API Integration

### 🧪 Unit Test Example

```php
// tests/Feature/ApiIntegrationTest.php
public function test_facebook_post_publishing()
{
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'id' => 'page_id_12345',
        ]),
    ]);

    $post = Post::factory()->create();
    $account = SocialAccount::factory()->facebook()->create();

    $service = new FacebookService();
    $result = $service->publishPost($post, $account);

    $this->assertTrue($result);
    $this->assertEquals('published', $post->fresh()->status);
}
```

### 🐛 Troubleshooting Checklist

- [ ] Verify API credentials in `.env`
- [ ] Check OAuth scopes are correct
- [ ] Confirm callback URL matches in platform settings
- [ ] Test access token is not expired
- [ ] Verify user has required permissions
- [ ] Check API rate limits
- [ ] Review error logs in `storage/logs/`
- [ ] Test with API testing tool (Postman, Insomnia)
- [ ] Verify HTTPS in production
- [ ] Check platform API documentation updates

---

## References & Resources

### Official Documentation
- [Facebook Graph API](https://developers.facebook.com/docs/graph-api)
- [Instagram Graph API](https://developers.facebook.com/docs/instagram-api)
- [X API v2](https://developer.x.com/en/docs/twitter-api)
- [LinkedIn API](https://learn.microsoft.com/en-us/linkedin/marketing/community-management)
- [YouTube API](https://developers.google.com/youtube/v3)
- [TikTok API](https://developers.tiktok.com/doc/web-api-intro)

### Related Documentation Files
- [SETUP_AND_DEPLOYMENT.md](./SETUP_AND_DEPLOYMENT.md)
- [COMPLETION_REPORT.md](./COMPLETION_REPORT.md)
- [README_DETAILED.md](./README_DETAILED.md)

### API Testing Tools
- Postman: https://www.postman.com/
- Insomnia: https://insomnia.rest/
- Thunder Client: https://www.thunderclient.com/

---

**Last Updated**: March 5, 2026
**API Documentation Version**: 1.0
**Status**: Production Ready ✅
