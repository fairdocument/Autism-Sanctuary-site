import React, { useMemo, useState } from "react";

type Payload = {
  firstName: string;
  lastName: string;
  orgAffiliation: string;
  email: string;
  phone: string;
  cityState: string;
  universityCommute: "Yes" | "No" | "";
  degreePrograms: string[];
  landAvailability: string;
  idealFitReason: string;
};

const degreeOptions = [
  "Occupational Therapy",
  "Special Education",
  "Psychology / Social Work",
  "Nursing / Pre-Med",
];

const initialState: Payload = {
  firstName: "",
  lastName: "",
  orgAffiliation: "",
  email: "",
  phone: "",
  cityState: "",
  universityCommute: "",
  degreePrograms: [],
  landAvailability: "",
  idealFitReason: "",
};

export default function CommunityInterestPage() {
  const [form, setForm] = useState<Payload>(initialState);
  const [busy, setBusy] = useState(false);
  const [status, setStatus] = useState<string>("");

  const canSubmit = useMemo(() => {
    return (
      form.firstName &&
      form.lastName &&
      form.orgAffiliation &&
      form.email &&
      form.phone &&
      form.cityState &&
      form.universityCommute &&
      form.landAvailability &&
      form.idealFitReason
    );
  }, [form]);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setStatus("");
    if (!canSubmit) {
      setStatus("Please complete all required fields.");
      return;
    }
    setBusy(true);
    try {
      const res = await fetch("/api/community-interest", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(form),
      });
      if (!res.ok) throw new Error("Failed");
      setStatus("Thank you. Your submission was received.");
      setForm(initialState);
    } catch {
      setStatus("Unable to submit right now. Please try again.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main>
      <h1>Bring a Sanctuary to Your Community</h1>
      <p style={{ maxWidth: "65ch", marginBottom: "1.5rem" }}>
        Share how your region can sustain <strong>person-centered support</strong>,{" "}
        <strong>care farming</strong>, and <strong>evidence-based practice</strong> for
        individuals with autism and IDD. This form is for civic and philanthropic
        partners; service referrals belong on the main Admissions pathway.
      </p>
      <form onSubmit={submit}>
        <input required placeholder="First Name" value={form.firstName} onChange={(e) => setForm({ ...form, firstName: e.target.value })} />
        <input required placeholder="Last Name" value={form.lastName} onChange={(e) => setForm({ ...form, lastName: e.target.value })} />
        <input required placeholder="Organization, Community Group, or Affiliation" value={form.orgAffiliation} onChange={(e) => setForm({ ...form, orgAffiliation: e.target.value })} />
        <input required type="email" placeholder="Email Address" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
        <input required placeholder="Phone Number" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
        <input required placeholder="City and State of Proposed Expansion" value={form.cityState} onChange={(e) => setForm({ ...form, cityState: e.target.value })} />

        <fieldset>
          <legend>Is the proposed community located within a 20 to 30-minute commute of a major college or university?</legend>
          <label><input type="radio" name="universityCommute" value="Yes" checked={form.universityCommute === "Yes"} onChange={() => setForm({ ...form, universityCommute: "Yes" })} />Yes</label>
          <label><input type="radio" name="universityCommute" value="No" checked={form.universityCommute === "No"} onChange={() => setForm({ ...form, universityCommute: "No" })} />No</label>
        </fieldset>

        <fieldset>
          <legend>Does this nearby university offer strong, established degree programs in any of the following? (Select all that apply)</legend>
          {degreeOptions.map((option) => (
            <label key={option}>
              <input
                type="checkbox"
                checked={form.degreePrograms.includes(option)}
                onChange={(e) => {
                  const next = e.target.checked
                    ? [...form.degreePrograms, option]
                    : form.degreePrograms.filter((d) => d !== option);
                  setForm({ ...form, degreePrograms: next });
                }}
              />
              {option}
            </label>
          ))}
        </fieldset>

        <textarea required placeholder="Briefly describe the agricultural or rural land availability in this area..." value={form.landAvailability} onChange={(e) => setForm({ ...form, landAvailability: e.target.value })} />
        <textarea required placeholder="Why do you believe your community is an ideal fit for an Autism Sanctuary location?" value={form.idealFitReason} onChange={(e) => setForm({ ...form, idealFitReason: e.target.value })} />

        <button disabled={busy} type="submit">{busy ? "Submitting..." : "Submit Community Interest Form"}</button>
      </form>
      {status ? <p>{status}</p> : null}
    </main>
  );
}
