-- Migration: Add chase tracking columns to leads table
-- Date: 2026-02-20

-- Add last_reply_at column
DO $$ 
BEGIN 
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='leads' AND column_name='last_reply_at'
    ) THEN 
        ALTER TABLE leads ADD COLUMN last_reply_at TIMESTAMP NULL;
    END IF;
END $$;

-- Add last_chase_at column
DO $$ 
BEGIN 
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='leads' AND column_name='last_chase_at'
    ) THEN 
        ALTER TABLE leads ADD COLUMN last_chase_at TIMESTAMP NULL;
    END IF;
END $$;

-- Add chase_count column
DO $$ 
BEGIN 
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='leads' AND column_name='chase_count'
    ) THEN 
        ALTER TABLE leads ADD COLUMN chase_count INTEGER DEFAULT 0 NOT NULL;
    END IF;
END $$;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_leads_last_reply_at ON leads(last_reply_at);
CREATE INDEX IF NOT EXISTS idx_leads_last_chase_at ON leads(last_chase_at);
CREATE INDEX IF NOT EXISTS idx_leads_chase_count ON leads(chase_count);

-- Populate initial data: Set last_reply_at based on conversations where sender is customer
UPDATE leads l
SET last_reply_at = (
    SELECT MAX(c.created_at)
    FROM conversations c
    WHERE c.lead_id = l.id
    AND c.sender_type IN ('customer')
    AND c.message_type = 'inbound'
)
WHERE l.last_reply_at IS NULL;

COMMENT ON COLUMN leads.last_reply_at IS 'Last time the lead replied to any message';
COMMENT ON COLUMN leads.last_chase_at IS 'Last time a chase/follow-up message was sent';
COMMENT ON COLUMN leads.chase_count IS 'Number of chase attempts for unresponsive leads';
