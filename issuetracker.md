# Current Support

## Crypto
- Generate KeyPair
- Reload Binance DEX Keystore
- Decode/Encode Addresses
- BIP39

## TxTypes
- 'MsgSend' 
- 'NewOrderMsg' 
- 'CancelOrderMsg' 
- 'FreezeMsg'  
- 'UnfreezeMsg' 
- 'HTLTMsg' 
- 'DepositHTLTMsg'
- 'ClaimHTLTMsg'
- 'RefundHTLTMsg'
- 'TimeLockMsg'
- 'TimeUnlockMsg'
- 'TimeRelockMsg'
- 'IssueMsg'
- 'BurnMsg'
- 'MintMsg'
- 'MsgSubmitProposal'
- 'MsgDeposit'
- 'MsgVote'

## RPC Get Methods
- getAccount

# Implementations still pending or partially implemented

## TxTypes:

- 'ListMsg' 
- 'SetAccountFlagsMsg'

## RPC Get Methods
- getTokenInfo
- listAllTokens
- getBalances
- getBalance
- getOpenOrders
- getTradingPairInfo
- getDepth

## Crypto 
- BIP32 (couldn't find any reliable up-to-date php library, will need to implement from scratch)
- Ledger (need to understand this)
- Export Keystore

