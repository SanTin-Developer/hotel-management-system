import emailjs, { EmailJSResponseStatus } from "@emailjs/browser";

import {
  EMAILJS_PUBLIC_KEY,
  EMAILJS_SERVICE_ID,
  EMAILJS_TEMPLATE_ID,
} from "@/constants/emailjs";
import { HOTEL_EMAIL, HOTEL_NAME } from "@/constants/routes";

export class EmailSendError extends Error {
  constructor(status, text) {
    super(text);
    this.name = "EmailSendError";
    this.status = status;
    this.text = text;
  }
}

export async function sendContactMessage({
  name,
  email,
  subject,
  message,
}) {
  try {
    const response = await emailjs.send(
      EMAILJS_SERVICE_ID,
      EMAILJS_TEMPLATE_ID,
      {
        from_name: name,
        from_email: email,
        reply_to: email,
        to_name: HOTEL_NAME,
        to_email: HOTEL_EMAIL,
        user_name: name,
        user_email: email,
        email: email,
        subject,
        message,
      },
      { publicKey: EMAILJS_PUBLIC_KEY },
    );

    return response.status;
  } catch (error) {
    if (error instanceof EmailJSResponseStatus) {
      throw new EmailSendError(error.status, error.text);
    }

    throw error;
  }
}