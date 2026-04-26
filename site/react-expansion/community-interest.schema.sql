CREATE TABLE IF NOT EXISTS community_interest_submissions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  org_affiliation TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT NOT NULL,
  city_state TEXT NOT NULL,
  university_commute TEXT NOT NULL CHECK (university_commute IN ('Yes', 'No')),
  degree_programs JSONB NOT NULL DEFAULT '[]'::jsonb,
  land_availability TEXT NOT NULL,
  ideal_fit_reason TEXT NOT NULL,
  submission_status TEXT NOT NULL DEFAULT 'new',
  source_page TEXT NOT NULL,
  consent_version TEXT NOT NULL,
  intake_owner TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_community_interest_created_at
  ON community_interest_submissions (created_at DESC);
