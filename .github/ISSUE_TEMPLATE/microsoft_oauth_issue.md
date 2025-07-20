---
name: 🔐 Microsoft OAuth Issue
about: Report issues specifically related to Microsoft authentication
title: '[OAUTH] '
labels: ['microsoft-oauth', 'authentication', 'needs-triage']
assignees: ''
---

# 🔐 Microsoft OAuth Issue

## 🎯 Issue Type
<!-- Mark the relevant option with an "x" -->
- [ ] 🚫 Cannot login with Microsoft account
- [ ] 🔗 Account linking/unlinking problems
- [ ] 👤 User information not syncing from AD
- [ ] 🔑 Token refresh issues
- [ ] 🏷️ Role assignment problems
- [ ] 🌍 Lithuanian name gender detection issues
- [ ] 🔒 Password restriction bypass
- [ ] 🗑️ Account deletion confirmation issues
- [ ] ⚙️ Configuration/setup problems

## 📋 Detailed Description
<!-- Provide a detailed description of the OAuth-specific issue -->

## 🔄 Authentication Flow Step
<!-- Where in the OAuth flow does the issue occur? -->
- [ ] Initial redirect to Microsoft
- [ ] Microsoft login page
- [ ] Consent/permission screen
- [ ] Callback to application
- [ ] User creation/linking
- [ ] Token storage/retrieval
- [ ] User information sync
- [ ] Post-login redirect

## 🌐 Microsoft Account Details
**Account Type:**
- [ ] Personal Microsoft account (@outlook.com, @hotmail.com, etc.)
- [ ] Work/School account (Azure AD)
- [ ] Vilnius University account (@knf.vu.lt)

**Tenant Information:**
- Tenant ID (if known): 
- Domain: 

## 🔧 Azure App Registration
**Configuration Details:**
- Client ID: `c14d9447-b88f-4d67-a797-e430242eb9c7` (if different, specify)
- Redirect URI configured: 
- Permissions granted: 

## 📊 Environment Variables
<!-- Check your .env configuration (DO NOT paste actual secrets) -->
- [ ] MSGRAPH_CLIENT_ID is set
- [ ] MSGRAPH_SECRET_ID is set  
- [ ] MSGRAPH_TENANT_ID is correct
- [ ] MSGRAPH_OAUTH_URL matches Azure config
- [ ] MSGRAPH_LANDING_URL is correct

## 🔍 Error Messages
<!-- Include any specific OAuth error messages -->
**Browser Error:**
```
Paste browser error here
```

**Laravel Log Error:**
```
Paste Laravel log error here
```

**Microsoft Error Code (if any):**
- Error Code: 
- Error Description: 

## 🧪 Steps to Reproduce
1. Go to login page
2. Click "Microsoft" button
3. [Continue with specific steps...]

## 👤 User Information Issues (if applicable)
**Expected User Data:**
- Name: 
- Email: 
- Expected Gender (based on Lithuanian name): 
- Expected Role: 

**Actual User Data:**
- Name: 
- Email: 
- Detected Gender: 
- Assigned Role: 

## 🔗 Account Linking Status
- [ ] User has existing PBPIS account
- [ ] User is trying to link Microsoft account
- [ ] User is trying to unlink Microsoft account
- [ ] New user registration via Microsoft

## 🌍 Lithuanian Name Detection (if applicable)
**Name:** 
**Expected Gender:** [Male/Female]
**Detected Gender:** [Male/Female]
**Surname Pattern:** [e.g., ends with -ienė, -as, etc.]

## 📱 Browser/Environment
- Browser: 
- Operating System: 
- Network: [University/Home/Corporate]
- Cookies/JavaScript enabled: [Yes/No]

## 🔄 Workarounds Attempted
- [ ] Cleared browser cache/cookies
- [ ] Tried different browser
- [ ] Tried incognito/private mode
- [ ] Cleared Laravel cache
- [ ] Restarted Docker containers
- [ ] Checked Azure app registration settings

## 📊 Impact
- [ ] Cannot access application at all
- [ ] Can login but features don't work
- [ ] Intermittent issues
- [ ] Affects specific user roles only
- [ ] Security concern

## 🔗 Related Issues
<!-- Link any related OAuth issues -->
Related to #
