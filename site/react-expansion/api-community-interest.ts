import { Pool } from "pg";

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

type Body = {
  firstName: string;
  lastName: string;
  orgAffiliation: string;
  email: string;
  phone: string;
  cityState: string;
  universityCommute: "Yes" | "No";
  degreePrograms: string[];
  landAvailability: string;
  idealFitReason: string;
};

export async function submitCommunityInterest(body: Body) {
  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    const insert = await client.query(
      `INSERT INTO community_interest_submissions
      (first_name,last_name,org_affiliation,email,phone,city_state,university_commute,degree_programs,land_availability,ideal_fit_reason,submission_status,source_page,consent_version,intake_owner)
      VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,'new','expansion','v1','national-intake')
      RETURNING id, created_at`,
      [
        body.firstName,
        body.lastName,
        body.orgAffiliation,
        body.email,
        body.phone,
        body.cityState,
        body.universityCommute,
        JSON.stringify(body.degreePrograms),
        body.landAvailability,
        body.idealFitReason,
      ]
    );

    await sendSubmitterReceiptStub(body.email, body.firstName);
    await sendInternalNotificationStub(insert.rows[0].id, body);
    await client.query("COMMIT");
    return insert.rows[0];
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }
}

async function sendSubmitterReceiptStub(email: string, firstName: string) {
  console.info("[email-stub] receipt", {
    to: email,
    subject: "Autism Sanctuary expansion inquiry received",
    greeting: `Hello ${firstName},`,
  });
}

async function sendInternalNotificationStub(submissionId: string, payload: Body) {
  console.info("[email-stub] internal-notification", {
    to: "expansion-intake@autismsanctuary.org",
    subject: `New expansion submission: ${submissionId}`,
    payload,
  });
}
