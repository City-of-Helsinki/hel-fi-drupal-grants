import path from "path";
import {logger} from "../logger";

/**
 * The AttachmentFiles interface.
 *
 * This interface describes all the available
 * attachments.
 */
interface AttachmentFiles  {
  YHTEISON_SAANNOT: string;
  VAHVISTETTU_TILINPAATOS: string;
  VAHVISTETTU_TOIMINTAKERTOMUS: string;
  VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS: string;
  VUOSIKOKOUKSEN_POYTAKIRJA: string;
  TOIMINTASUUNNITELMA: string;
  TALOUSARVIO: string;
  MUU_LIITE: string;
  LEIRIEXCEL: string;
  TEST_PDF: string;
  TEST_EXCEL: string;
  BANK_ACCOUNT_CONFIRMATION: string;
}

/**
 * The prefixAttachmentPaths function.
 *
 * This function adds a ".../data/attachments/" prefix
 * to all attachments.
 *
 * @param attachments {AttachmentFiles}
 *   An object containing all the attachments.
 */
const prefixAttachmentPaths = (attachments: AttachmentFiles): AttachmentFiles => {
  const basePath = path.join(__dirname, './attachments/');
  const prefixedAttachments: AttachmentFiles = {} as AttachmentFiles;

  Object.keys(attachments).forEach((key) => {
    const fileName = attachments[key as keyof AttachmentFiles];
    prefixedAttachments[key as keyof AttachmentFiles] = path.join(basePath, fileName);
  });

  return prefixedAttachments;
};

/**
 * Raw attachment data from the .env file.
 */
const RAW_ATTACHMENTS: AttachmentFiles = {
  YHTEISON_SAANNOT: process.env.YHTEISON_SAANNOT ?? '',
  VAHVISTETTU_TILINPAATOS: process.env.VAHVISTETTU_TILINPAATOS ?? '',
  VAHVISTETTU_TOIMINTAKERTOMUS: process.env.VAHVISTETTU_TOIMINTAKERTOMUS ?? '',
  VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS: process.env.VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS ?? '',
  VUOSIKOKOUKSEN_POYTAKIRJA: process.env.VUOSIKOKOUKSEN_POYTAKIRJA ?? '',
  TOIMINTASUUNNITELMA: process.env.TOIMINTASUUNNITELMA ?? '',
  TALOUSARVIO: process.env.TALOUSARVIO ?? '',
  MUU_LIITE: process.env.MUU_LIITE ?? '',
  LEIRIEXCEL: process.env.LEIRIEXCEL ?? '',
  TEST_PDF: process.env.TEST_PDF ?? '',
  TEST_EXCEL: process.env.TEST_EXCEL ?? '',
  BANK_ACCOUNT_CONFIRMATION: process.env.BANK_ACCOUNT_CONFIRMATION ?? '',
}

/**
 * Prefix attachments and export.
 */
const ATTACHMENTS = prefixAttachmentPaths(RAW_ATTACHMENTS);
logger('ALL', ATTACHMENTS);
export {
  ATTACHMENTS,
};
