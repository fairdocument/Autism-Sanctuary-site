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
      <h1>Future locations — community interest</h1>
      <p style={{ maxWidth: "65ch", marginBottom: "1.5rem" }}>
        Autism Sanctuary is strengthening its Western Albemarle campus for the next year
        or more. This form is for civic, university, or philanthropic partners exploring{" "}
        <em>possible</em> future sites after those systems mature. We combine{" "}
        <strong>evidence-based methods</strong> with <strong>promising practices</strong>{" "}
        developed on our farm. <strong>Service referrals</strong> still belong with
        Admissions—not here.
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
