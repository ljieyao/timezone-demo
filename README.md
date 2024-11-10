# User Sync Feature Documentation

## Overview

This feature synchronizes user data with a third-party API service, handling large batches of users while respecting API rate limits. The system processes user updates by batching users in groups of 1,000, queuing updates to respect rate limits (50 batches/hour), providing robust error handling and retry logic, with comprehensive logging throughout the process.

## Components

1. UserSyncService

-   Handles batching of users into groups of 1,000
-   Manages job dispatching with 72-second delays between batches
-   Ensures compliance with rate limits
-   Logs batch creation and dispatch events

2. ExternalUserApi

-   Manages all communication with the third-party API
-   Handles API responses and error conditions
-   Provides detailed logging of API interactions
-   Tracks request durations and response statuses

3. SyncUserBatch Job

-   Processes individual batches of users
-   Implements 3-attempt retry logic with 60-second backoff
-   Logs job progress, attempts, and failures
-   Handles permanent failures appropriately

## API Rate Limits

-   Maximum 50 batch requests per hour
-   Maximum 1,000 records per batch
-   Total capacity: 50,000 updates per hour

## Configuration Required

### Environment Variables:

USER_API_URL=https://api.example.com
USER_API_KEY=your-api-key

### Queue Configuration:

-   Uses 'user-sync' queue
-   Database driver recommended
-   90-second retry_after setting

## Usage Examples

1. Basic Usage:

    ```php
    $users = User::where('updated_at', '>', now()->subHour())->get();
    $syncService = new UserSyncService();
    $syncService->syncUsers($users);
    ```

2. Queue Worker:
    ```bash
    php artisan queue:work --queue=user-sync
    ```

## Error Handling Strategy

-   Three total attempts per job
-   60-second delay between retry attempts
-   Comprehensive error logging
-   Failed jobs stored in failed_jobs table

## Logging Implementation

1. Service Level Logs:

-   Batch creation events
-   Total users and batch counts
-   Scheduling information

2. API Level Logs:

-   Request/response details
-   Duration tracking
-   Error conditions
-   Request IDs for tracing

3. Job Level Logs:

-   Processing status
-   Retry attempts
-   Failure conditions
-   Job IDs for tracking

## Data Format

The API expects batched user data in this structure:

```json
{
    "batches": [
        {
            "subscribers": [
                {
                    "email": "user@example.com",
                    "name": "John Doe",
                    "time_zone": "UTC"
                }
            ]
        }
    ]
}
```

## Troubleshooting Guide

1. Common Issues:

    - Rate limit exceeded
    - API timeouts
    - Data validation errors
    - Queue worker issues

2. Resolution Steps:

    - Check logs for error details
    - Verify API credentials
    - Review batch configurations
    - Check network connectivity

3. Prevention:
    - Regular log review
    - Monitor queue performance
    - Track API rate limits
    - Test data validation

## Testing

Run tests with:

```bash
php artisan test --filter=UserSyncTest
```

Test coverage includes:

-   Batch size validation
-   API error handling
-   Rate limit compliance
-   Retry logic verification
-   Log entry validation
