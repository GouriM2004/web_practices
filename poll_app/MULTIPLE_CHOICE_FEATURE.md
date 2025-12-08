# Multiple Choice Feature Implementation Summary

## Overview

Added full support for multiple choice polls where users can select more than one option, plus optional voter identity display.

## Database Changes

### New Column

- **Table**: `polls`
- **Column**: `allow_multiple` (TINYINT, default 0)
- **Migration**: `sql/add_multiple_choice.sql` (for existing installations)
- **Main Schema**: Updated `sql/poll_app.sql` (for fresh installations)

### Voter Identity (Security + Trust)

- **Table**: `voters` (name + password)
- **Columns on poll_votes**: `voter_id`, `voter_name`, `is_public`, plus unique locks on `(poll_id, voter_id)` and `(poll_id, voter_ip)`
- **Migration**: `sql/add_voter_auth.sql`

### Migration for Existing Databases

Run this SQL command:

```sql
ALTER TABLE polls ADD COLUMN allow_multiple TINYINT(1) DEFAULT 0 AFTER is_active;
```

## Code Changes

### 1. Poll Model (`includes/Models/Poll.php`)

- **`recordVote()`**: Accepts array of option_ids, processes multiple votes, blocks duplicates by voter account or IP, stores voter visibility preference
- **`createPoll()`**: Added `$allow_multiple` parameter to set poll type
- **`getPublicVoters()`**: Lists voters who opted to display their name

### 2. Vote Processing (`vote.php`)

- Changed to accept `option_id` as either single value or array
- Normalizes input to array format for consistent processing
- Validates all selected option IDs
- Requires voter login, records public/anonymous choice per vote

### 3. Poll Display (`index.php`)

- Conditionally renders:
  - **Checkboxes** (`option_id[]`) for multiple choice polls
  - **Radio buttons** (`option_id`) for single choice polls
- Shows "You may select multiple options" hint for multiple choice
- Client-side validation ensures at least one option selected
- Requires login to submit; offers public/anonymous toggle checkbox

### 4. Poll Creation (`admin/create_poll.php`)

- Added checkbox: "Allow multiple choice"
- Passes `allow_multiple` flag to `Poll::createPoll()`

### 5. Admin Dashboard (`admin/dashboard.php`)

- Added "Type" column showing Single/Multiple badge
- Blue badge for Multiple choice polls
- Gray badge for Single choice polls

### 6. Voter Auth (`includes/VoterAuth.php`, `includes/Models/Voter.php`, `voter_login.php`, `voter_logout.php`)

- Simple name+password login/registration
- Session-based voter identity available to voting flow
- Public/anonymous choice captured at vote time

## User Experience

### For Voters

- **Single Choice**: Radio buttons (select one)
- **Multiple Choice**: Checkboxes (select one or more)
- Clear visual indication of poll type
- Validation prevents submitting without selection

### For Admins

- Simple checkbox toggle when creating polls
- Dashboard clearly shows poll type
- No changes needed for existing polls (default to single choice)

## Testing Steps

1. **Run Migration**: Execute `sql/add_multiple_choice.sql` in phpMyAdmin
2. **Run Voter Migration**: Execute `sql/add_voter_auth.sql`
3. **Create Single Choice Poll**: Leave "Allow multiple choice" unchecked
4. **Create Multiple Choice Poll**: Check "Allow multiple choice"
5. **Test Voting**: Verify radio buttons vs checkboxes
6. **Check Results**: Ensure all votes are recorded correctly
7. **Verify Dashboard**: Confirm Type column shows correct badges
8. **Test Login Requirement**: Ensure vote submission redirects to voter login when not authenticated
9. **Test Public/Anonymous**: Submit votes with and without "show my name" and confirm results list shows only opted-in names

## Backward Compatibility

- Existing polls automatically work as single choice (allow_multiple=0)
- No data migration required for existing polls (but run migrations to add columns/tables)
- Old voting behavior preserved for single choice polls
