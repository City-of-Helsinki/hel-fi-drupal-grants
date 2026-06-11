// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
export type Official = any;

export type GrantsProfile = {
  // Community fields
  companyNameShort?: string;
  companyName?: string;
  companyHome?: string;
  companyHomePage?: string;
  companyStatus?: string;
  companyStatusSpecial?: string;
  businessPurpose?: string;
  foundingYear?: string;
  registrationDate?: string;
  officials?: Array<Official>;
  businessId?: string;
  // Private person fields
  firstName?: string;
  lastName?: string;
  socialSecurityNumber?: string;
  email?: string;
  phone_number?: string;
  // Shared
  addresses: Array<{ address_id: string; street: string; postCode: string; city: string; country: string }>;
  bankAccounts: Array<{
    bankAccount: string;
    confirmationFile: string;
    bank_account_id: string;
    ownerName?: string;
    ownerSsn?: string;
  }>;
};
