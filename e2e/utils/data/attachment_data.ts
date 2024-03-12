import path from "path";

/**
 * The AttachmentFiles interface.
 *
 * This interface describes all the available
 * attachment files. The files are located in
 * the "/attachments" directory.
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
 * Attachment filenames.
 */
const ATTACHMENT_FILES: AttachmentFiles = {
  YHTEISON_SAANNOT: '00_yhteison_saannot.pdf',
  VAHVISTETTU_TILINPAATOS: '01_vahvistettu_tilinpaatos.pdf',
  VAHVISTETTU_TOIMINTAKERTOMUS: '02_vahvistettu_toimintakertomus.pdf',
  VAHVISTETTU_TILIN_TAI_TOIMINNANTARKASTUSKERTOMUS: '03_vahvistettu_tilin_tai_toiminnantarkastuskertomus.pdf',
  VUOSIKOKOUKSEN_POYTAKIRJA: '04_vuosikokouksen_poytakirja.pdf',
  TOIMINTASUUNNITELMA: '05_toimintasuunnitelma.pdf',
  TALOUSARVIO: '06_talousarvio.pdf',
  MUU_LIITE: '07_muu_liite.pdf',
  LEIRIEXCEL: 'la_leiriavustushakemus.xls',
  TEST_PDF: 'test.pdf',
  TEST_EXCEL: 'test.xlsx',
  BANK_ACCOUNT_CONFIRMATION: 'test.pdf',
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
 * Prefix attachments and export.
 */
const ATTACHMENTS = prefixAttachmentPaths(ATTACHMENT_FILES);

export {
  ATTACHMENTS,
};
