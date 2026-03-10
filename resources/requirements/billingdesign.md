For going live, i only need these  6 endpoints now to work 100% perfect and fine

1. SUBSCRIPTION
-once a new customer account is created (a business) and trial period is over, user can opt to choose a respective package and in that stage, system must be able to create subscription for a new customer based on package selected and then system should provide an option to a user to pay via  ucn and link to pay via flutterwave and stripe
-on each successful login, system must check this user/business is active on which subscription so only respective contracts are enforced 
-user should be able to upgrade subscription plan either from basic to standard or to premium, and during upgrade endpoint must return correct pending amount to be paid

2. Wallet
-on creating subscription, system should ablso create a wallet for a new customer to monitor ai credits, and this wallet should have a unique ucn number with option to pay via other parameters
-get wallet status, how many credits remained/used
-topup wallet (give me ucn and link to pay via flutterwave and stripe)c