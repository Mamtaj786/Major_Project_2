-- Add bio, education, and skills columns to signup table
ALTER TABLE signup
ADD COLUMN bio TEXT DEFAULT NULL,
ADD COLUMN education TEXT DEFAULT NULL,
ADD COLUMN skills TEXT DEFAULT NULL;

-- Add post_type column to posts table if it doesn't exist
ALTER TABLE posts
ADD COLUMN post_type VARCHAR(20) DEFAULT 'general'; 